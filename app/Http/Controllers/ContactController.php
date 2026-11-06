<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Mail\ContactMessage;

class ContactController extends Controller
{
    /**
     * Handle contact form submission
     */
    public function send(Request $request)
    {
        // Validate the form data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10|max:5000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get admin email from environment variable
            $adminEmail = env('ADMIN_EMAIL');

            if (!$adminEmail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Admin email not configured. Please contact system administrator.'
                ], 500);
            }

            // -------- HTML EMAIL START --------
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "From: {$request->name} <{$request->email}>\r\n";
            $headers .= "Reply-To: {$request->email}\r\n";

            $body = '
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
                   New Message from ' . config('app.name') . '
                </div>
                <div class="email-body">
                    <p><span class="label">Name:</span> ' . $request->name . '</p>
                    <p><span class="label">Email:</span> ' . $request->email . '</p>
                    <p><span class="label">Subject:</span> ' . $request->subject . '</p>

                    <div class="message-box">
                        ' . $request->message . '
                    </div>
                </div>
                <div class="email-footer">
                    This message was sent from ' . config('app.name') . ' contact form.
                </div>
            </div>
        </body>
        </html>';

            $sent = mail(
                $adminEmail,
                $request->subject,
                $body,
                $headers
            );

            if (!$sent) {
                throw new \Exception('Mail function failed.');
            }
            // -------- HTML EMAIL END --------

            return response()->json([
                'success' => true,
                'message' => 'Your message has been sent successfully!'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }


}
