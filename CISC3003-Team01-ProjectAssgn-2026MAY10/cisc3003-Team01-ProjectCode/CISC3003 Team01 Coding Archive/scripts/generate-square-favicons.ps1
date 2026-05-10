# Generate square PNG favicons from brand image — "cover" scale (fills the square like Google),
# centered and clipped (no empty letterboxing).
$ErrorActionPreference = 'Stop'
$root = Split-Path -Parent $PSScriptRoot
if (-not $root) { $root = (Get-Location).Path }
$srcPath = Join-Path $root 'assets\images\brand\um-rental.png'
if (-not (Test-Path $srcPath)) {
    $srcPath = Join-Path $root 'UM Rental.png'
}
if (-not (Test-Path $srcPath)) { throw "Source image not found: $srcPath" }

Add-Type -AssemblyName System.Drawing

function Export-SquareIcon {
    param(
        [string]$SourcePath,
        [string]$OutPath,
        [int]$Box
    )
    $src = [System.Drawing.Image]::FromFile((Resolve-Path $SourcePath))
    try {
        $bmp = New-Object System.Drawing.Bitmap($Box, $Box, [System.Drawing.Imaging.PixelFormat]::Format32bppArgb)
        $g = [System.Drawing.Graphics]::FromImage($bmp)
        try {
            $g.SmoothingMode = [System.Drawing.Drawing2D.SmoothingMode]::HighQuality
            $g.InterpolationMode = [System.Drawing.Drawing2D.InterpolationMode]::HighQualityBicubic
            $g.PixelOffsetMode = [System.Drawing.Drawing2D.PixelOffsetMode]::HighQuality
            $g.Clear([System.Drawing.Color]::Transparent)
            $sw = [float]$src.Width
            $sh = [float]$src.Height
            # Cover: scale so both dimensions >= Box (fills square; excess cropped).
            $scale = [Math]::Max($Box / $sw, $Box / $sh)
            $nw = $sw * $scale
            $nh = $sh * $scale
            $x = ($Box - $nw) / 2.0
            $y = ($Box - $nh) / 2.0
            $clipRect = New-Object System.Drawing.Rectangle 0, 0, $Box, $Box
            $g.SetClip($clipRect)
            $dest = New-Object System.Drawing.RectangleF($x, $y, $nw, $nh)
            $g.DrawImage($src, $dest)
        }
        finally { $g.Dispose() }
        $dir = Split-Path -Parent $OutPath
        if (-not (Test-Path $dir)) { New-Item -ItemType Directory -Path $dir -Force | Out-Null }
        $bmp.Save($OutPath, [System.Drawing.Imaging.ImageFormat]::Png)
    }
    finally {
        $bmp.Dispose()
        $src.Dispose()
    }
}

$outDir = Join-Path $root 'assets\images\brand'
Export-SquareIcon -SourcePath $srcPath -OutPath (Join-Path $outDir 'favicon-16.png') -Box 16
Export-SquareIcon -SourcePath $srcPath -OutPath (Join-Path $outDir 'favicon-32.png') -Box 32
Export-SquareIcon -SourcePath $srcPath -OutPath (Join-Path $outDir 'apple-touch-icon.png') -Box 180
Write-Host "OK: favicon-16.png, favicon-32.png, apple-touch-icon.png -> $outDir"
