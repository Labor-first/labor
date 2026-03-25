<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>重置密码验证码</title>
</head>
<body style="font-family: 'Microsoft YaHei', Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 20px;">
    <div style="max-width: 600px; margin: 0 auto; background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); overflow: hidden;">
        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;">
            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">密码重置验证码</h1>
        </div>
        
        <div style="padding: 30px;">
            <p style="color: #333; font-size: 16px; line-height: 1.6;">尊敬的 <strong>{{ $username }}</strong>：</p>
            
            <p style="color: #666; font-size: 14px; line-height: 1.8;">您正在进行密码重置操作，请使用以下验证码完成身份验证：</p>
            
            <div style="background-color: #f8f9fa; border: 2px dashed #667eea; border-radius: 8px; padding: 20px; text-align: center; margin: 25px 0;">
                <span style="font-size: 36px; font-weight: bold; color: #667eea; letter-spacing: 8px;">{{ $code }}</span>
            </div>
            
            <div style="background-color: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;">
                <p style="margin: 0; color: #856404; font-size: 14px;">
                    <strong>⚠️ 注意事项：</strong>
                </p>
                <ul style="margin: 10px 0 0 0; padding-left: 20px; color: #856404; font-size: 13px;">
                    <li>验证码有效期为 <strong>10分钟</strong></li>
                    <li>验证码错误次数超过5次将失效</li>
                    <li>如非本人操作，请忽略此邮件</li>
                </ul>
            </div>
            
            <p style="color: #999; font-size: 12px; border-top: 1px solid #eee; padding-top: 20px; margin-top: 30px;">
                此邮件由系统自动发送，请勿直接回复。<br>
                如有疑问，请联系管理员。
            </p>
        </div>
    </div>
</body>
</html>
