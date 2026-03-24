<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>账号激活码</title>
    <style>
        .email-container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 8px;
            font-family: "Microsoft YaHei", sans-serif;
        }
        .code-box {
            font-size: 24px;
            font-weight: bold;
            color: #2d7dff;
            margin: 20px 0;
            padding: 10px;
            background-color: #f8f9fa;
            border-radius: 4px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <h2>您好，{{ $username }}！</h2>
        <p>您正在进行实验室管理系统账号激活，以下是您的激活码：</p>
        <div class="code-box">{{ $activationCode }}</div>
        <p>该激活码有效期为 <strong>{{ $expireMinutes }}</strong> 分钟，请尽快完成激活，过期将失效。</p>
        <p>如果不是您本人操作，请忽略此邮件，您的账号安全不会受到影响。</p>
        <p>实验室管理系统团队</p>
    </div>
</body>
</html>