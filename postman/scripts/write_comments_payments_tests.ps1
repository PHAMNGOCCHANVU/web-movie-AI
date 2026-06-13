# Script to write test scripts to Comments & Ratings and Subscription & Payment request YAML files

# --- COMMENTS & RATINGS ---

# Post Comment
$content = @'
$kind: http-request
name: Post Comment
method: POST
url: '{{base_url}}/movies/{{movie_id}}/comments'
auth:
  type: bearer
  credentials:
    - key: token
      value: '{{token}}'
body:
  type: json
  content: |-
    {
      "content": "Phim rất hay, tôi rất thích!"
    }
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Post Comment - Status 201", function () {
          pm.response.to.have.status(201);
      });

      pm.test("Post Comment - Response has comment data", function () {
          const json = pm.response.json();
          pm.expect(json).to.have.property("data");
          pm.expect(json.data).to.have.property("id");
          pm.expect(json.data.content).to.equal("Phim rất hay, tôi rất thích!");
          
          pm.environment.set("test_comment_id", json.data.id.toString());
          pm.environment.set("comment_id", json.data.id.toString());
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Comments & Ratings/Post Comment.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Post Comment"

# Get Movie Comments
$content = @'
$kind: http-request
name: Get Movie Comments
method: GET
url: '{{base_url}}/movies/{{movie_id}}/comments'
queryParams:
  - key: page
    value: '1'
  - key: limit
    value: '20'
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Get Movie Comments - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("Get Movie Comments - Response is array or paginated", function () {
          const json = pm.response.json();
          const isArray = Array.isArray(json);
          const isPaginated = json && (Array.isArray(json.data) || Array.isArray(json.comments) || (json.data && Array.isArray(json.data.data)));
          pm.expect(isArray || isPaginated).to.be.true;
      });

      pm.test("Get Movie Comments - Each comment has required fields", function () {
          const json = pm.response.json();
          const dataObj = json.data || json;
          const comments = Array.isArray(dataObj) ? dataObj : (dataObj.data || dataObj.comments || []);
          if (comments.length > 0) {
              comments.forEach(function (comment) {
                  pm.expect(comment).to.have.property("id");
                  pm.expect(comment.content !== undefined || comment.body !== undefined).to.be.true;
                  pm.expect(comment).to.have.property("user");
              });
          }
      });

      const json = pm.response.json();
      const dataObj = json.data || json;
      const comments = Array.isArray(dataObj) ? dataObj : (dataObj.data || dataObj.comments || []);
      if (comments.length > 0) {
          pm.environment.set("test_comment_id", comments[0].id.toString());
          pm.environment.set("comment_id", comments[0].id.toString());
      }
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Comments & Ratings/Get Movie Comments.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Get Movie Comments"

# Rate Movie
$content = @'
$kind: http-request
name: Rate Movie
method: POST
url: '{{base_url}}/movies/{{movie_id}}/ratings'
auth:
  type: bearer
  credentials:
    - key: token
      value: '{{token}}'
body:
  type: json
  content: |-
    {
      "score": 8
    }
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Rate Movie - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("Rate Movie - Response has rating data", function () {
          const json = pm.response.json();
          pm.expect(json).to.have.property("data");
          pm.expect(json.data).to.have.property("score");
          pm.expect(json.data.score).to.equal(8);
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Comments & Ratings/Rate Movie.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Rate Movie"

# Get Movie Rating Summary
$content = @'
$kind: http-request
name: Get Movie Rating Summary
method: GET
url: '{{base_url}}/movies/{{movie_id}}/ratings'
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Get Rating Summary - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("Get Rating Summary - Has rating data", function () {
          const json = pm.response.json();
          const data = json.data || json;
          const hasAverage = data.average !== undefined || data.avg_rating !== undefined || data.rating !== undefined;
          const hasTotal = data.total !== undefined || data.count !== undefined;
          pm.expect(hasAverage).to.be.true;
          pm.expect(hasTotal).to.be.true;
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Comments & Ratings/Get Movie Rating Summary.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Get Movie Rating Summary"

# Delete Own Comment
$content = @'
$kind: http-request
name: Delete Own Comment
method: DELETE
url: '{{base_url}}/comments/{{comment_id}}'
auth:
  type: bearer
  credentials:
    - key: token
      value: '{{token}}'
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Delete Comment - Status 200 or 404", function () {
          pm.expect(pm.response.code).to.be.oneOf([200, 404]);
      });

      pm.test("Delete Comment - Response has message", function () {
          const json = pm.response.json();
          pm.expect(json).to.have.property("message");
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Comments & Ratings/Delete Own Comment.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Delete Own Comment"


# --- SUBSCRIPTION & PAYMENT ---

# Get Subscription Plans
$content = @'
$kind: http-request
name: Get Subscription Plans
method: GET
url: '{{base_url}}/subscription/plans'
description: |
  Trả về danh sách gói cước với các lựa chọn monthly & yearly.
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Get Subscription Plans - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("Get Subscription Plans - Response is array or has plans", function () {
          const json = pm.response.json();
          const plans = Array.isArray(json) ? json : (json.data || []);
          pm.expect(plans.length).to.be.above(0);
          
          const standard = plans.find(p => p.plan_code === "standard_monthly");
          pm.expect(standard).to.exist;
          pm.expect(standard.price).to.equal(49000);
          
          pm.environment.set("test_plan_code", "standard_monthly");
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Subscription & Payment/Get Subscription Plans.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Get Subscription Plans"

# Create VNPay Payment
$content = @'
$kind: http-request
name: Create VNPay Payment
method: POST
url: '{{base_url}}/payment/vnpay/create'
auth:
  type: bearer
  credentials:
    - key: token
      value: '{{token}}'
body:
  type: json
  content: |-
    {
      "plan_code": "standard_monthly",
      "billing_cycle": "monthly",
      "transaction_type": "purchase"
    }
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Create Payment - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("Create Payment - Has required fields", function () {
          const json = pm.response.json();
          pm.expect(json).to.have.property("data");
          pm.expect(json.data).to.have.property("payment_url");
          pm.expect(json.data).to.have.property("transaction_id");
          pm.expect(json.data).to.have.property("vnp_txn_ref");
          
          pm.environment.set("test_vnp_txn_ref", json.data.vnp_txn_ref);
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Subscription & Payment/Create VNPay Payment.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Create VNPay Payment"

# Create VNPay Payment - Upgrade to VIP
$content = @'
$kind: http-request
name: Create VNPay Payment - Upgrade to VIP
method: POST
url: '{{base_url}}/payment/vnpay/create'
auth:
  type: bearer
  credentials:
    - key: token
      value: '{{token}}'
body:
  type: json
  content: |-
    {
      "plan_code": "vip_monthly",
      "transaction_type": "upgrade",
      "billing_cycle": "monthly"
    }
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Create Upgrade Payment - Status 200 or 400", function () {
          pm.expect(pm.response.code).to.be.oneOf([200, 400]);
      });

      pm.test("Create Upgrade Payment - Response verification if 200", function () {
          if (pm.response.code === 200) {
              const json = pm.response.json();
              pm.expect(json).to.have.property("data");
              pm.expect(json.data).to.have.property("payment_url");
              pm.expect(json.data).to.have.property("vnp_txn_ref");
              
              pm.environment.set("test_vnp_txn_ref_vip", json.data.vnp_txn_ref);
          }
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Subscription & Payment/Create VNPay Payment - Upgrade to VIP.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Create VNPay Payment - Upgrade to VIP"

# VNPay IPN Webhook
$content = @'
$kind: http-request
name: VNPay IPN Webhook
method: POST
url: '{{base_url}}/payment/vnpay/ipn'
body:
  type: json
  content: |-
    {
      "vnp_ResponseCode": "00",
      "vnp_TxnRef": "{{test_vnp_txn_ref}}",
      "vnp_Amount": "4900000",
      "vnp_SecureHash": "{{vnp_SecureHash}}"
    }
scripts:
  - type: beforeRequest
    language: text/javascript
    code: |-
      const txnRef = pm.environment.get("test_vnp_txn_ref") || "TXN123456";
      const amount = "4900000";
      const responseCode = "00";
      
      const params = {
          vnp_Amount: amount,
          vnp_ResponseCode: responseCode,
          vnp_TxnRef: txnRef
      };
      
      const sortedKeys = Object.keys(params).sort();
      const queryParts = [];
      sortedKeys.forEach(key => {
          queryParts.push(key + "=" + params[key]);
      });
      const hashData = queryParts.join("&");
      const hashSecret = pm.environment.get("vnp_hash_secret") || "";
      const hash = CryptoJS.HmacSHA512(hashData, hashSecret).toString();
      
      pm.environment.set("vnp_SecureHash", hash);
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("VNPay IPN - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("VNPay IPN - Response code 00", function () {
          const json = pm.response.json();
          pm.expect(json).to.have.property("RspCode");
          pm.expect(json.RspCode).to.equal("00");
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Subscription & Payment/VNPay IPN Webhook.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: VNPay IPN Webhook"

# VNPay Return Callback
$content = @'
$kind: http-request
name: VNPay Return Callback
method: GET
url: '{{base_url}}/payment/vnpay/return'
queryParams:
  - key: vnp_ResponseCode
    value: '00'
  - key: vnp_TxnRef
    value: '{{test_vnp_txn_ref}}'
  - key: vnp_Amount
    value: '4900000'
  - key: vnp_SecureHash
    value: '{{vnp_SecureHash}}'
scripts:
  - type: beforeRequest
    language: text/javascript
    code: |-
      const txnRef = pm.environment.get("test_vnp_txn_ref") || "TXN123456";
      const amount = "4900000";
      const responseCode = "00";
      
      const params = {
          vnp_Amount: amount,
          vnp_ResponseCode: responseCode,
          vnp_TxnRef: txnRef
      };
      
      const sortedKeys = Object.keys(params).sort();
      const queryParts = [];
      sortedKeys.forEach(key => {
          queryParts.push(key + "=" + params[key]);
      });
      const hashData = queryParts.join("&");
      const hashSecret = pm.environment.get("vnp_hash_secret") || "";
      const hash = CryptoJS.HmacSHA512(hashData, hashSecret).toString();
      
      pm.environment.set("vnp_SecureHash", hash);
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("VNPay Return - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("VNPay Return - Has data", function () {
          const json = pm.response.json();
          pm.expect(json).to.have.property("data");
          pm.expect(json.data.is_valid).to.be.true;
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Subscription & Payment/VNPay Return Callback.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: VNPay Return Callback"

# Get User Subscription Details
$content = @'
$kind: http-request
name: Get User Subscription Details
method: GET
url: '{{base_url}}/user/subscription'
auth:
  type: bearer
  credentials:
    - key: token
      value: '{{token}}'
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Get Subscription Details - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("Get Subscription Details - Check active status", function () {
          const json = pm.response.json();
          const data = json.data || json;
          const user = data.user || data;
          pm.expect(user).to.have.property("subscription_status");
          pm.expect(user.subscription_status).to.be.oneOf(["active", "none", "expired"]);
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Subscription & Payment/Get User Subscription Details.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Get User Subscription Details"

# Cancel Subscription
$content = @'
$kind: http-request
name: Cancel Subscription
method: POST
url: '{{base_url}}/subscription/cancel'
auth:
  type: bearer
  credentials:
    - key: token
      value: '{{token}}'
body:
  type: json
  content: |-
    {
      "cancellation_reason": "Tạm thời không cần xem phim"
    }
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Cancel Subscription - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("Cancel Subscription - Response has message", function () {
          const json = pm.response.json();
          pm.expect(json).to.have.property("message");
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Subscription & Payment/Cancel Subscription.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Cancel Subscription"

# Get User Payment History
$content = @'
$kind: http-request
name: Get User Payment History
method: GET
url: '{{base_url}}/user/payment-history'
auth:
  type: bearer
  credentials:
    - key: token
      value: '{{token}}'
scripts:
  - type: afterResponse
    language: text/javascript
    code: |-
      pm.test("Get Payment History - Status 200", function () {
          pm.response.to.have.status(200);
      });

      pm.test("Get Payment History - Response is array or has transactions", function () {
          const json = pm.response.json();
          const dataObj = json.data || json;
          const items = Array.isArray(dataObj) ? dataObj : (dataObj.data || dataObj.transactions || []);
          pm.expect(Array.isArray(items)).to.be.true;
      });
'@
[System.IO.File]::WriteAllText('postman/collections/Web Movie AI - Full API/Subscription & Payment/Get User Payment History.request.yaml', $content, [System.Text.Encoding]::UTF8)
Write-Host "Written: Get User Payment History"

Write-Host "All Comments and Subscriptions test files written successfully!"
