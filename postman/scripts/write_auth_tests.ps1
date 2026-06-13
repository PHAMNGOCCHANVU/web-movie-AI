# Script to write correct scripts format to Auth request YAML files

# Register
$content = @'
$kind: http-request
name: Register
method: POST
url: '{{base_url}}/auth/register'
body:
  type: json
  content: |-
    {
      "name": "Nguyen Van A",
      "email": "{{test_email}}",
      "password": "{{test_password}}",
      "password_confirmation": "{{test_password}}"
    }
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Register - Status 201/200", function () {
          pm.expect(pm.response.code).to.be.oneOf([200, 201, 422]);
          if (pm.response.code === 422) {
              const json = pm.response.json();
              pm.expect(json.message || json.errors).to.exist;
          }
      });

      pm.test("Register - Response has token", function () {
          if (pm.response.code === 422) {
              pm.expect(pm.response.code).to.equal(422);
              return;
          }
          const json = pm.response.json();
          const token = json.token || (json.data && json.data.token) || json.access_token;
          pm.expect(token).to.be.a('string').and.not.empty;
          if (token) {
              pm.environment.set("token", token);
          }
      });

      pm.test("Register - Response has user data", function () {
          if (pm.response.code === 422) {
              pm.expect(pm.response.code).to.equal(422);
              return;
          }
          const json = pm.response.json();
          const user = json.user || (json.data && json.data.user) || json.data;
          pm.expect(user).to.be.an('object');
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Auth/Register.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Register"

# Login
$content = @'
$kind: http-request
name: Login
method: POST
url: '{{base_url}}/auth/login'
body:
  type: json
  content: |-
    {
      "email": "{{test_email}}",
      "password": "{{test_password}}"
    }
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Login - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("Login - Response has token", function () {
          const json = pm.response.json();
          const token = json.token || (json.data && json.data.token) || json.access_token;
          pm.expect(token).to.be.a('string').and.not.empty;
      });

      pm.test("Login - Token saved to environment", function () {
          const json = pm.response.json();
          const token = json.token || (json.data && json.data.token) || json.access_token;
          pm.expect(token).to.be.a('string').and.not.empty;
          pm.environment.set("token", token);
      });

      pm.test("Login - Response has user data", function () {
          const json = pm.response.json();
          const user = json.user || (json.data && json.data.user) || json.data;
          pm.expect(user).to.be.an('object');
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Auth/Login.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Login"

# Get Current User
$content = @'
$kind: http-request
name: Get Current User
method: GET
url: '{{base_url}}/auth/me'
auth:
  type: bearer
  credentials:
    - key: token
      value: '{{token}}'
description: |
  Response gồm role (user|admin) và subscription:
  { plan_code, status, starts_at, expires_at }
  Free: plan_code null, status none.
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Get Me - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("Get Me - Has user fields", function () {
          const json = pm.response.json();
          const user = json.data || json;
          pm.expect(user).to.have.property('id');
          pm.expect(user).to.have.property('email');
          pm.expect(user).to.have.property('name');
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Auth/Get Current User.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Get Current User"

# Logout
$content = @'
$kind: http-request
name: Logout
method: POST
url: '{{base_url}}/auth/logout'
auth:
  type: bearer
  credentials:
    - key: token
      value: '{{token}}'
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Logout - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("Logout - Token cleared", function () {
          pm.environment.unset("token");
          pm.expect(pm.environment.get("token")).to.be.undefined;
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Auth/Logout.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Logout"

Write-Host "Auth files fixed successfully!"
