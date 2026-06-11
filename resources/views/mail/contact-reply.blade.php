<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réponse à votre message</title>
    <style>
        body  { margin:0; padding:0; background:#f5f5f4; font-family:'Segoe UI',Arial,sans-serif; color:#292524; }
        .wrap { max-width:560px; margin:40px auto; padding:0 16px; }
        .card { background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.08); }
        .header { background:#92400e; padding:32px 36px; }
        .header h1 { margin:0; color:#fef3c7; font-size:20px; font-weight:700; letter-spacing:-.3px; }
        .header p  { margin:4px 0 0; color:#fde68a; font-size:13px; }
        .body { padding:32px 36px; }
        .body p  { margin:0 0 16px; font-size:15px; line-height:1.6; color:#44403c; }
        .reply-box { background:#fffbeb; border:1px solid #fde68a; border-radius:8px;
                     padding:16px 18px; margin:0 0 20px; font-size:15px; line-height:1.6; color:#44403c; white-space:pre-line; }
        .original { background:#f5f5f4; border:1px solid #e7e5e4; border-radius:8px;
                    padding:14px 18px; margin:20px 0 0; font-size:13px; color:#78716c; }
        .original .label { font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:#a8a29e; margin-bottom:6px; }
        .original .subject { font-weight:600; color:#57534e; margin-bottom:4px; }
        .original .message { white-space:pre-line; line-height:1.5; }
        .footer { padding:20px 36px; border-top:1px solid #f5f5f4; }
        .footer p { margin:0; font-size:12px; color:#a8a29e; text-align:center; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="header">
            <h1>☕ Coffee Shop</h1>
            <p>Réponse à votre message</p>
        </div>
        <div class="body">
            <p>Bonjour <strong>{{ $contact->name }}</strong>,</p>
            <p>
                Nous vous remercions de nous avoir contactés. Voici la réponse de notre équipe
                à votre message :
            </p>

            <div class="reply-box">{{ $replyMessage }}</div>

            <div class="original">
                <p class="label">Votre message d'origine</p>
                @if($contact->subject)
                    <p class="subject">{{ $contact->subject }}</p>
                @endif
                <p class="message">{{ $contact->message }}</p>
            </div>
        </div>
        <div class="footer">
            <p>Pour toute nouvelle question, n'hésitez pas à nous écrire de nouveau via notre formulaire de contact.</p>
        </div>
    </div>
</div>
</body>
</html>
