param(
    [string]$ProductsFile = '.tmp\catalog-import\products.normalized.json',
    [string]$SelectionsFile = 'tools\catalog_import\image_selections.json',
    [string]$ExclusionsFile = 'tools\catalog_import\public_exclusions.json',
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
$exclusions = @()
if (Test-Path -LiteralPath $ExclusionsFile) {
    $exclusions = @((Get-Content -Raw -LiteralPath $ExclusionsFile | ConvertFrom-Json).models)
}
$results = @()

foreach ($product in $products) {
    if ($product.model -in $exclusions) {
        continue
    }
    $selectionProperty = $selections.PSObject.Properties[$product.model]
    if ($null -eq $selectionProperty) {
        throw "No image selection for model $($product.model)"
    }
    $selection = $selectionProperty.Value
    $approvedSource = $selection.PSObject.Properties['source']
    $approvedSourcePath = if ($null -ne $approvedSource) { [string]$approvedSource.Value } else { '' }
    if ($approvedSourcePath) {
        $source = [IO.Path]::GetFullPath($approvedSourcePath.Replace('/', [IO.Path]::DirectorySeparatorChar))
        if (-not (Test-Path -LiteralPath $source -PathType Leaf)) {
            throw "Approved image source not found for model $($product.model): $approvedSourcePath"
        }
        $sourceLabel = $approvedSourcePath
    } else {
        $candidateIndex = [int]$selection.candidate - 1
        if ($candidateIndex -lt 0 -or $candidateIndex -ge $product.media_candidates.Count) {
            throw "Invalid image candidate for model $($product.model)"
        }
        $candidate = $product.media_candidates[$candidateIndex]
        $relativeSource = $candidate.path.Replace('/', [IO.Path]::DirectorySeparatorChar)
        $source = [IO.Path]::GetFullPath((Join-Path $ExtractedRoot $relativeSource))
        $sourceLabel = $candidate.path
    }
    $dimensions = (& $magick identify -format '%w %h' $source).Split(' ')
    $width = [int]$dimensions[0]
    $height = [int]$dimensions[1]
    $outputDirectory = Join-Path $OutputRoot $product.family
    $output = Join-Path $outputDirectory "$($selection.slug)-1.webp"

    if ($selection.generate -eq $true -or $width -lt 640 -or $height -lt 480) {
        $results += [pscustomobject][ordered]@{
            model = $product.model; status = 'generation-reference'; source = $sourceLabel
            width = $width; height = $height; output = $null
        }
        continue
    }

    New-Item -ItemType Directory -Path $outputDirectory -Force | Out-Null
    $copySourceProperty = $selection.PSObject.Properties['copy_source']
    $copySource = $null -ne $copySourceProperty -and $copySourceProperty.Value -eq $true
    if ($copySource) {
        Copy-Item -LiteralPath $source -Destination $output -Force
    } elseif ($approvedSourcePath) {
        & $magick $source -auto-orient -colorspace sRGB -resize '1080x810>' -background '#ffffff' -gravity center -extent 1200x900 -alpha remove -alpha off -strip -quality 88 -define 'webp:method=6' $output
    } else {
        & $magick $source -auto-orient -resize '1920x1920>' -strip -quality 84 -define 'webp:method=6' $output
    }
    if ($LASTEXITCODE -ne 0 -or -not (Test-Path -LiteralPath $output)) {
        throw "Image conversion failed for $($product.model)"
    }
    $results += [pscustomobject][ordered]@{
        model = $product.model; status = $(if ($approvedSourcePath) { 'published-approved-source' } else { 'published-original' }); source = $sourceLabel
        width = $width; height = $height; output = $output.Replace('\\', '/')
    }
}

$summaryDirectory = Split-Path -Parent $SummaryFile
New-Item -ItemType Directory -Path $summaryDirectory -Force | Out-Null
$summaryJson = $results | ConvertTo-Json -Depth 5
[IO.File]::WriteAllText([IO.Path]::GetFullPath($SummaryFile), $summaryJson, [Text.UTF8Encoding]::new($false))
$results | Group-Object status | Select-Object Name, Count
