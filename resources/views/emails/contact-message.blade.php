<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f8;
            padding: 20px;
        }
        .email-container {
            background-color: #ffffff;
            max-width: 600px;
            margin: auto;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .email-header {
            background-color: #0d6efd;
            color: #ffffff;
            padding: 20px;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
        }
        .email-body {
            padding: 20px;
            color: #333333;
        }
        .email-body p {
            margin: 8px 0;
        }
        .label {
            font-weight: bold;
        }
        .message-box {
            margin-top: 15px;
            padding: 15px;
            background-color: #f1f1f1;
            border-radius: 6px;
            white-space: pre-line;
        }
        .email-footer {
            padding: 15px;
            text-align: center;
            font-size: 12px;
            color: #888888;
            background-color: #fafafa;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
           New Message from {{ config('app.name') }}
        </div>
        <div class="email-body">
            <p><span class="label">Name:</span> {{ $name }}</p>
            <p><span class="label">Email:</span> {{ $email }}</p>
            <p><span class="label">Subject:</span> {{ $subject }}</p>

            <div class="message-box">
                {{ $messageContent }}
            </div>
        </div>
        <div class="email-footer">
            This message was sent from {{ config('app.name') }} contact form.
        </div>
    </div>
</body>
</html>
