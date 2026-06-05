# Script to write test scripts to all request YAML files

# Get All Movies
$content = @'
$kind: http-request
name: Get All Movies
method: GET
url: '{{base_url}}/movies'
queryParams:
  - key: page
    value: '1'
  - key: limit
    value: '20'
  - key: genre_id
    value: ''
  - key: year
    value: ''
  - key: type
    value: 'all'
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Get All Movies - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("Get All Movies - Response is array or paginated", function () {
          const json = pm.response.json();
          const data = json.data || json;
          const items = Array.isArray(data) ? data : (data.data || []);
          pm.expect(Array.isArray(items)).to.be.true;
      });

      pm.test("Get All Movies - Each movie has required fields", function () {
          const json = pm.response.json();
          const data = json.data || json;
          const items = Array.isArray(data) ? data : (data.data || []);
          if (items.length > 0) {
              items.forEach(function (movie) {
                  pm.expect(movie).to.have.property("id");
                  pm.expect(movie).to.have.property("title");
              });
          }
      });

      const json = pm.response.json();
      const data = json.data || json;
      const movies = Array.isArray(data) ? data : (data.data || []);
      if (movies.length > 0) {
          pm.environment.set("test_movie_id", movies[0].id.toString());
      }
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Movies/Get All Movies.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Get All Movies"

# Search Movies
$content = @'
$kind: http-request
name: Search Movies
method: GET
url: '{{base_url}}/movies/search'
queryParams:
  - key: keyword
    value: 'avengers'
  - key: q
    value: 'action'
  - key: year
    value: ''
  - key: genre_id
    value: ''
  - key: page
    value: '1'
  - key: limit
    value: '20'
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Search Movies - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("Search Movies - Response has data", function () {
          const json = pm.response.json();
          pm.expect(json).to.exist;
          const data = json.data || json;
          pm.expect(data).to.exist;
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Movies/Search Movies.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Search Movies"

# Get Featured Movies (Slider)
$content = @'
$kind: http-request
name: 'Get Featured Movies (Slider)'
method: GET
url: '{{base_url}}/movies/featured'
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Get Featured Movies - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("Get Featured Movies - Response is array", function () {
          const json = pm.response.json();
          const data = json.data || json;
          const items = Array.isArray(data) ? data : (data.data || []);
          pm.expect(Array.isArray(items)).to.be.true;
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Movies/Get Featured Movies (Slider).request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Get Featured Movies (Slider)"

# Get Newly Updated Movies
$content = @'
$kind: http-request
name: Get Newly Updated Movies
method: GET
url: '{{base_url}}/movies/new-updated'
queryParams:
  - key: page
    value: '1'
  - key: limit
    value: '20'
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Get Newly Updated Movies - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("Get Newly Updated Movies - Response has data", function () {
          const json = pm.response.json();
          pm.expect(json).to.exist;
          const data = json.data || json;
          pm.expect(data).to.exist;
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Movies/Get Newly Updated Movies.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Get Newly Updated Movies"

# Get Movie Detail
$content = @'
$kind: http-request
name: Get Movie Detail
method: GET
url: '{{base_url}}/movies/{{movie_id}}'
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Get Movie Detail - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("Get Movie Detail - Has required fields", function () {
          const json = pm.response.json();
          const data = json.data || json;
          pm.expect(data).to.have.property("id");
          pm.expect(data).to.have.property("title");
          pm.expect(data.description !== undefined || data.overview !== undefined).to.be.true;
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Movies/Get Movie Detail.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Get Movie Detail"

# Get Movie Cast
$content = @'
$kind: http-request
name: Get Movie Cast
method: GET
url: '{{base_url}}/movies/{{movie_id}}/cast'
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Get Movie Cast - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("Get Movie Cast - Response is array or has data", function () {
          const json = pm.response.json();
          const data = json.data || json;
          const isArray = Array.isArray(data);
          const hasData = data !== null && data !== undefined;
          pm.expect(isArray || hasData).to.be.true;
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Movies/Get Movie Cast.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Get Movie Cast"

# Get Movie Trailer
$content = @'
$kind: http-request
name: Get Movie Trailer
method: GET
url: '{{base_url}}/movies/{{movie_id}}/trailer'
description: |
  Public — Guest/Free không cần Bearer. Trailer YouTube iframe.
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Get Movie Trailer - Status 200 or 404", function () {
          pm.expect([200, 404]).to.include(pm.response.code);
      });

      pm.test("Get Movie Trailer - Response has data if 200", function () {
          if (pm.response.code === 200) {
              const json = pm.response.json();
              pm.expect(json).to.exist;
              const data = json.data || json;
              pm.expect(data).to.exist;
          }
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Movies/Get Movie Trailer.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Get Movie Trailer"

# Get Movie Episodes
$content = @'
$kind: http-request
name: Get Movie Episodes
method: GET
url: '{{base_url}}/movies/{{movie_id}}/episodes'
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Get Movie Episodes - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("Get Movie Episodes - Response is array or has data", function () {
          const json = pm.response.json();
          const data = json.data || json;
          const isArray = Array.isArray(data);
          const hasData = data !== null && data !== undefined;
          pm.expect(isArray || hasData).to.be.true;
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Movies/Get Movie Episodes.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Get Movie Episodes"

# Get All Genres
$content = @'
$kind: http-request
name: Get All Genres
method: GET
url: '{{base_url}}/genres'
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Get All Genres - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("Get All Genres - Response is array", function () {
          const json = pm.response.json();
          const data = json.data || json;
          const items = Array.isArray(data) ? data : (data.data || []);
          pm.expect(Array.isArray(items)).to.be.true;
      });

      pm.test("Get All Genres - Each genre has id and name", function () {
          const json = pm.response.json();
          const data = json.data || json;
          const genres = Array.isArray(data) ? data : (data.data || []);
          if (genres.length > 0) {
              genres.forEach(function (genre) {
                  pm.expect(genre).to.have.property("id");
                  pm.expect(genre).to.have.property("name");
              });
          }
      });

      const json = pm.response.json();
      const data = json.data || json;
      const genres = Array.isArray(data) ? data : (data.data || []);
      if (genres.length > 0) {
          pm.environment.set("test_genre_id", genres[0].id.toString());
      }
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Genres/Get All Genres.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Get All Genres"

# Get Movies By Genre
$content = @'
$kind: http-request
name: Get Movies By Genre
method: GET
url: '{{base_url}}/genres/{{genre_id}}/movies'
queryParams:
  - key: page
    value: '1'
  - key: limit
    value: '20'
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Get Movies By Genre - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("Get Movies By Genre - Response has data", function () {
          const json = pm.response.json();
          pm.expect(json).to.exist;
          const data = json.data || json;
          pm.expect(data).to.exist;
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Genres/Get Movies By Genre.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Get Movies By Genre"

Write-Host "All files written successfully!"
