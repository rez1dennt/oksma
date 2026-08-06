param(
    [Parameter(Mandatory = $true)][string]$SourceDirectory,
    [Parameter(Mandatory = $true)][string]$DestinationDirectory
)

$ErrorActionPreference = 'Stop'
$sourceRoot = (Resolve-Path -LiteralPath $SourceDirectory).Path
$destinationRoot = [IO.Path]::GetFullPath((Join-Path (Get-Location).Path $DestinationDirectory))
New-Item -ItemType Directory -Path $destinationRoot -Force | Out-Null

$word = $null
try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $word.DisplayAlerts = 0

    foreach ($source in Get-ChildItem -LiteralPath $sourceRoot -Recurse -File -Filter '*.doc') {
        $relative = $source.FullName.Substring($sourceRoot.Length).TrimStart([char[]](92, 47))
        $target = [IO.Path]::ChangeExtension((Join-Path $destinationRoot $relative), '.docx')
        New-Item -ItemType Directory -Path ([IO.Path]::GetDirectoryName($target)) -Force | Out-Null
        $document = $null
        try {
            $document = $word.Documents.Open($source.FullName, $false, $true)
            $document.SaveAs2($target, 16)
            Write-Output "$($source.FullName) -> $target"
        }
        finally {
            if ($null -ne $document) {
                $document.Close($false)
                [Runtime.InteropServices.Marshal]::ReleaseComObject($document) | Out-Null
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
