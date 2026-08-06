param(
    [string]$ProductsFile = '.tmp\catalog-import\products.normalized.json',
    [string]$SelectionsFile = 'tools\catalog_import\image_selections.json',
    [string]$ExtractedRoot = '.tmp\catalog-import\extracted',
    [string]$OutputRoot = 'assets\images\products',
    [string]$SummaryFile = '.tmp\catalog-import\prepared-images.json'
)

$ErrorActionPreference = 'Stop'
$magick = 'C:\Program Files\ImageMagick-7.1.2-Q16-HDRI\magick.exe'
if (-not (Test-Path -LiteralPath $magick)) {
    throw "ImageMagick not found: $magick"
}

$products = Get-Content -Raw -LiteralPath $ProductsFile | ConvertFrom-Json
$selections = Get-Content -Raw -LiteralPath $SelectionsFile | ConvertFrom-Json
$results = @()

foreach ($product in $products) {
    $selectionProperty = $selections.PSObject.Properties[$product.model]
    if ($null -eq $selectionProperty) {
        throw "No image selection for model $($product.model)"
    }
    $selection = $selectionProperty.Value
    $candidateIndex = [int]$selection.candidate - 1
    if ($candidateIndex -lt 0 -or $candidateIndex -ge $product.media_candidates.Count) {
        throw "Invalid image candidate for model $($product.model)"
    }
    $candidate = $product.media_candidates[$candidateIndex]
    $relativeSource = $candidate.path.Replace('/', [IO.Path]::DirectorySeparatorChar)
    $source = [IO.Path]::GetFullPath((Join-Path $ExtractedRoot $relativeSource))
    $dimensions = (& $magick identify -format '%w %h' $source).Split(' ')
    $width = [int]$dimensions[0]
    $height = [int]$dimensions[1]
    $outputDirectory = Join-Path $OutputRoot $product.family
    $output = Join-Path $outputDirectory "$($selection.slug)-1.webp"

    if ($selection.generate -eq $true -or $width -lt 640 -or $height -lt 480) {
        $results += [pscustomobject][ordered]@{
            model = $product.model; status = 'generation-reference'; source = $candidate.path
            width = $width; height = $height; output = $null
        }
        continue
    }

    New-Item -ItemType Directory -Path $outputDirectory -Force | Out-Null
    & $magick $source -auto-orient -resize '1920x1920>' -strip -quality 84 -define 'webp:method=6' $output
    if ($LASTEXITCODE -ne 0 -or -not (Test-Path -LiteralPath $output)) {
        throw "Image conversion failed for $($product.model)"
    }
    $results += [pscustomobject][ordered]@{
        model = $product.model; status = 'published-original'; source = $candidate.path
        width = $width; height = $height; output = $output.Replace('\\', '/')
    }
}

$summaryDirectory = Split-Path -Parent $SummaryFile
New-Item -ItemType Directory -Path $summaryDirectory -Force | Out-Null
$summaryJson = $results | ConvertTo-Json -Depth 5
[IO.File]::WriteAllText([IO.Path]::GetFullPath($SummaryFile), $summaryJson, [Text.UTF8Encoding]::new($false))
$results | Group-Object status | Select-Object Name, Count
