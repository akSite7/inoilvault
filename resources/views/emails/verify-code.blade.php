<!DOCTYPE html>
<html lang="ru">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <title>Код подтверждения</title>
    </head>
    <body style="margin: 0; padding: 0; background-color: #0f0f0f; color: #f0f0f0; font-family: Arial, sans-serif;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #0f0f0f; padding: 32px 16px;">
            <tr>
                <td align="center">
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 520px; background-color: #1b1b1b; border-radius: 12px; padding: 24px;">
                        <tr>
                            <td style="font-size: 20px; font-weight: 700; padding-bottom: 12px;">
                                Подтверждение почты
                            </td>
                        </tr>
                        <tr>
                            <td style="font-size: 14px; line-height: 1.6; color: #cfcfcf;">
                                Ваш код подтверждения:
                            </td>
                        </tr>
                        <tr>
                            <td style="padding: 16px 0;">
                                <div style="display: inline-block; font-size: 28px; letter-spacing: 6px; font-weight: 700; background-color: #101010; padding: 12px 18px; border-radius: 8px; color: #ffffff;">
                                    {{ $code }}
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td style="font-size: 12px; color: #9a9a9a; line-height: 1.6;">
                                Код действует 30 минут. Если вы не регистрировались, просто проигнорируйте письмо.
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
