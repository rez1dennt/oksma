param(
    [Parameter(Mandatory = $true)][string[]]$SourceDirectories,
    [Parameter(Mandatory = $true)][string]$DestinationDirectory
)

$ErrorActionPreference = 'Stop'
$destinationRoot = [IO.Path]::GetFullPath((Join-Path (Get-Location).Path $DestinationDirectory))
New-Item -ItemType Directory -Path $destinationRoot -Force | Out-Null

$word = $null
try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0
    foreach ($sourceDirectory in $SourceDirectories) {
        $sourceRoot = (Resolve-Path -LiteralPath $sourceDirectory).Path
        $rootLabel = Split-Path -Leaf $sourceRoot
        foreach ($source in Get-ChildItem -LiteralPath $sourceRoot -Recurse -File -Filter '*.docx') {
            $relative = $source.FullName.Substring($sourceRoot.Length).TrimStart([char[]](92, 47))
            $target = [IO.Path]::ChangeExtension((Join-Path (Join-Path $destinationRoot $rootLabel) $relative), '.pdf')
            New-Item -ItemType Directory -Path ([IO.Path]::GetDirectoryName($target)) -Force | Out-Null
            $document = $null
            try {
                $document = $word.Documents.Open($source.FullName, $false, $true)
                $document.ExportAsFixedFormat($target, 17)
                Write-Output "$($source.Name) -> $target"
            }
            finally {
                if ($null -ne $document) {
                    $document.Close($false)
                    [Runtime.InteropServices.Marshal]::ReleaseComObject($document) | Out-Null
                }
            }
        }
    }
}
finally {
    if ($null -ne $word) {
        $word.Quit()
        [Runtime.InteropServices.Marshal]::ReleaseComObject($word) | Out-Null
    }
    [GC]::Collect()
    [GC]::WaitForPendingFinalizers()
}
