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
foreach ($f in $files) {
  $content = [System.IO.File]::ReadAllText($f, [System.Text.Encoding]::UTF8)
  $newContent = $content -replace "`r`n", "`n"
  [System.IO.File]::WriteAllText($f, $newContent, [System.Text.Encoding]::UTF8)
  Write-Host "Converted: $f"
}
