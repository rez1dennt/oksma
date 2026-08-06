param(
    [Parameter(Mandatory = $true)][string]$SourceZip,
    [Parameter(Mandatory = $true)][string]$Destination
)

$ErrorActionPreference = 'Stop'
$source = (Resolve-Path -LiteralPath $SourceZip).Path
$workspace = [IO.Path]::GetFullPath((Get-Location).Path)
$allowedRoot = [IO.Path]::GetFullPath((Join-Path $workspace '.tmp\catalog-import'))
$dest = [IO.Path]::GetFullPath((Join-Path $workspace $Destination))
$comparison = [StringComparison]::OrdinalIgnoreCase

if (-not ($dest.Equals($allowedRoot, $comparison) -or $dest.StartsWith($allowedRoot + [IO.Path]::DirectorySeparatorChar, $comparison))) {
    throw 'Destination must be inside the repository .tmp/catalog-import directory.'
}

Add-Type -AssemblyName System.IO.Compression.FileSystem
$archive = [IO.Compression.ZipFile]::OpenRead($source)
try {
    foreach ($entry in $archive.Entries) {
        $entryPath = $entry.FullName.Replace('/', [IO.Path]::DirectorySeparatorChar)
        if ($entryPath -match '(^|[\\/])\.\.([\\/]|$)') {
            throw "Unsafe ZIP entry: $($entry.FullName)"
        }
        $target = [IO.Path]::GetFullPath((Join-Path $dest $entryPath))
        if (-not ($target.Equals($dest, $comparison) -or $target.StartsWith($dest + [IO.Path]::DirectorySeparatorChar, $comparison))) {
            throw "ZIP entry escapes destination: $($entry.FullName)"
        }
    }
}
finally {
    $archive.Dispose()
}

New-Item -ItemType Directory -Path $dest -Force | Out-Null
Expand-Archive -LiteralPath $source -DestinationPath $dest -Force
Write-Output $dest
