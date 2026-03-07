<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Welcome to {{ config('app.name') }}</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      background-color: #f4f7fa;
      color: #333;
    }

    .email-container {
      max-width: 600px;
      margin: 40px auto;
      background-color: #ffffff;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .email-header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      padding: 40px 30px;
      text-align: center;
      color: #ffffff;
    }

    .email-header h1 {
      margin: 0;
      font-size: 28px;
      font-weight: 600;
    }

    .email-header p {
      margin: 10px 0 0;
      font-size: 16px;
      opacity: 0.9;
    }

    .email-body {
      padding: 40px 30px;
    }

    .greeting {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 20px;
      color: #333;
    }

    .content-text {
      font-size: 15px;
      line-height: 1.6;
      color: #555;
      margin-bottom: 20px;
    }

    .credentials-box {
      background-color: #f8f9fa;
      border-left: 4px solid #667eea;
      padding: 20px;
      margin: 25px 0;
      border-radius: 4px;
    }

    .credentials-box h3 {
      margin: 0 0 15px;
      font-size: 16px;
      color: #333;
      font-weight: 600;
    }

    .credential-item {
      margin: 12px 0;
      font-size: 14px;
    }

    .credential-label {
      font-weight: 600;
      color: #666;
      display: inline-block;
      width: 100px;
    }

    .credential-value {
      color: #333;
      font-family: 'Courier New', monospace;
      background-color: #fff;
      padding: 4px 8px;
      border-radius: 3px;
      border: 1px solid #e0e0e0;
    }

    .button-container {
      text-align: center;
      margin: 30px 0;
    }

    .button {
      display: inline-block;
      padding: 14px 32px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: #ffffff !important;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 600;
      font-size: 16px;
      transition: transform 0.2s;
    }

    .button:hover {
      transform: translateY(-2px);
    }

    .instructions {
      background-color: #fff8e1;
      border-left: 4px solid #ffc107;
      padding: 20px;
      margin: 25px 0;
      border-radius: 4px;
    }

    .instructions h3 {
      margin: 0 0 12px;
      font-size: 16px;
      color: #f57c00;
      font-weight: 600;
    }

    .instructions ol {
      margin: 0;
      padding-left: 20px;
    }

    .instructions li {
      margin: 8px 0;
      font-size: 14px;
      color: #666;
      line-height: 1.5;
    }

    .email-footer {
      background-color: #f8f9fa;
      padding: 30px;
      text-align: center;
      border-top: 1px solid #e0e0e0;
    }

    .email-footer p {
      margin: 5px 0;
      font-size: 13px;
      color: #777;
    }

    .email-footer a {
      color: #667eea;
      text-decoration: none;
    }

    .divider {
      height: 1px;
      background-color: #e0e0e0;
      margin: 25px 0;
    }

    @media only screen and (max-width: 600px) {
      .email-container {
        margin: 20px;
      }

      .email-header,
      .email-body,
      .email-footer {
        padding: 25px 20px;
      }

      .button {
        padding: 12px 24px;
        font-size: 14px;
      }
    }
  </style>
</head>

<body>
  <div class="email-container">
    <!-- Header -->
    <div class="email-header">
      <h1>Welcome to {{ config('app.name') }}</h1>
      <p>Your account has been created</p>
    </div>

    <!-- Body -->
    <div class="email-body">
      <div class="greeting">
        Hello {{ $user->title }} {{ $user->name }},
      </div>

      <p class="content-text">
        Welcome to the {{ config('app.name') }} system! Your account has been successfully created
        with the role of <strong>{{ $roleName }}</strong>.
      </p>

      <p class="content-text">
        You can now access the system using the credentials below. For security reasons, you will be
        required to change your password upon your first login.
      </p>

      <!-- Credentials Box -->
      <div class="credentials-box">
        <h3>🔐 Your Login Credentials</h3>
        <div class="credential-item">
          <span class="credential-label">Email:</span>
          <span class="credential-value">{{ $user->email }}</span>
        </div>
        <div class="credential-item">
          <span class="credential-label">Password:</span>
          <span class="credential-value">{{ $password }}</span>
        </div>
        <div class="credential-item">
          <span class="credential-label">Role:</span>
          <span class="credential-value">{{ $roleName }}</span>
        </div>
      </div>

      <!-- Login Button -->
      <div class="button-container">
        <a href="{{ $loginUrl }}" class="button">Access System</a>
      </div>

      <div class="divider"></div>

      <!-- Instructions -->
      <div class="instructions">
        <h3>⚠️ First Time Login Instructions</h3>
        <ol>
          <li>Click the "Access System" button above or visit: <a href="{{ $loginUrl }}">{{ $loginUrl }}</a>
          </li>
          <li>Enter your email address and the temporary password provided above</li>
          <li>You will be prompted to create a new secure password</li>
          <li>Choose a strong password with at least 8 characters</li>
          <li>After changing your password, you'll have full access to the system</li>
        </ol>
      </div>

      <p class="content-text" style="margin-top: 25px;">
        <strong>Important:</strong> Please keep your login credentials secure and do not share them with anyone.
        If you experience any issues accessing your account, please contact the system administrator.
      </p>
    </div>

    <!-- Footer -->
    <div class="email-footer">
      <p><strong>{{ config('app.name') }}</strong></p>
      <p>Makerere University Business School</p>
      <p style="margin-top: 15px;">
        <a href="{{ config('app.url') }}">{{ config('app.url') }}</a>
      </p>
      <p style="margin-top: 15px; font-size: 12px; color: #999;">
        This is an automated message. Please do not reply to this email.
      </p>
    </div>
  </div>
</body>

</html>
