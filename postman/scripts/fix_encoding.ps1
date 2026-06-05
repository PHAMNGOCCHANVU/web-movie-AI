$files = @(
  'postman/collections/Web Movie AI - Full API/Movies/Get All Movies.request.yaml',
  'postman/collections/Web Movie AI - Full API/Movies/Search Movies.request.yaml',
  'postman/collections/Web Movie AI - Full API/Movies/Get Featured Movies (Slider).request.yaml',
  'postman/collections/Web Movie AI - Full API/Movies/Get Newly Updated Movies.request.yaml',
  'postman/collections/Web Movie AI - Full API/Movies/Get Movie Detail.request.yaml',
  'postman/collections/Web Movie AI - Full API/Movies/Get Movie Cast.request.yaml',
  'postman/collections/Web Movie AI - Full API/Movies/Get Movie Trailer.request.yaml',
  'postman/collections/Web Movie AI - Full API/Movies/Get Movie Episodes.request.yaml',
  'postman/collections/Web Movie AI - Full API/Genres/Get All Genres.request.yaml',
  'postman/collections/Web Movie AI - Full API/Genres/Get Movies By Genre.request.yaml'
)
$utf8NoBom = New-Object System.Text.UTF8Encoding $false
foreach ($f in $files) {
  $bytes = [System.IO.File]::ReadAllBytes($f)
  # Strip BOM if present
  if ($bytes.Length -ge 3 -and $bytes[0] -eq 0xEF -and $bytes[1] -eq 0xBB -and $bytes[2] -eq 0xBF) {
    $bytes = $bytes[3..($bytes.Length-1)]
  }
  $text = [System.Text.Encoding]::UTF8.GetString($bytes)
  # Normalize CRLF to LF
  $text = $text.Replace("`r`n", "`n")
  [System.IO.File]::WriteAllText($f, $text, $utf8NoBom)
  Write-Host "Fixed: $f"
}
Write-Host "Done!"
