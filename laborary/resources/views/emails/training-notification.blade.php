<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: "Microsoft YaHei", Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h2 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .content h3 {
            color: #333;
            margin-top: 0;
            font-size: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        .content p {
            color: #666;
            font-size: 16px;
            line-height: 1.8;
        }
        .footer {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            color: #999;
            font-size: 12px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h2>📢 培训通知</h2>
    </div>
    <div class="content">
        <h3>{{ $title }}</h3>
        <p>{!! nl2br(e($content)) !!}</p>
    </div>
    <div class="footer">
        <p>此邮件由实验室管理系统自动发送，请勿回复</p>
        <p>如有疑问，请联系管理员</p>
    </div>
</div>
</body>
</html>
