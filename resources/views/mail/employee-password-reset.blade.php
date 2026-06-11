<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation de mot de passe</title>
    <style>
        body  { margin:0; padding:0; background:#f5f5f4; font-family:'Segoe UI',Arial,sans-serif; color:#292524; }
        .wrap { max-width:560px; margin:40px auto; padding:0 16px; }
        .card { background:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.08); }
        .header { background:#92400e; padding:32px 36px; }
        .header h1 { margin:0; color:#fef3c7; font-size:20px; font-weight:700; letter-spacing:-.3px; }
        .header p  { margin:4px 0 0; color:#fde68a; font-size:13px; }
        .body { padding:32px 36px; }
        .body p  { margin:0 0 16px; font-size:15px; line-height:1.6; color:#44403c; }
        .btn-wrap { text-align:center; margin:28px 0; }
        .btn { display:inline-block; background:#b45309; color:#ffffff !important; text-decoration:none;
               padding:13px 32px; border-radius:8px; font-size:15px; font-weight:600; letter-spacing:-.2px; }
        .btn:hover { background:#92400e; }
        .url-box { background:#f5f5f4; border:1px solid #e7e5e4; border-radius:6px;
                   padding:10px 14px; margin:0 0 20px; word-break:break-all;
                   font-size:12px; color:#78716c; font-family:monospace; }
        .warning { background:#fffbeb; border:1px solid #fde68a; border-radius:8px;
                   padding:12px 16px; margin:20px 0 0; font-size:13px; color:#92400e; }
        .warning strong { display:block; margin-bottom:2px; }
        .footer { padding:20px 36px; border-top:1px solid #f5f5f4; }
        .footer p { margin:0; font-size:12px; color:#a8a29e; text-align:center; }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <div class="header">
            <h1>☕ Coffee Shop — Espace Salarié</h1>
            <p>Réinitialisation de mot de passe</p>
        </div>
        <div class="body">
            <p>Bonjour <strong>{{ $employee->name }}</strong>,</p>
            @if($isNewAccount)
            <p>
                Un compte salarié vient d'être créé pour vous sur l'espace <strong>Coffee Shop</strong>.
                Cliquez sur le bouton ci-dessous pour définir votre mot de passe et accéder à votre espace.
            </p>
            @else
            <p>
                Un super administrateur a déclenché une réinitialisation de votre mot de passe.
                Cliquez sur le bouton ci-dessous pour définir un nouveau mot de passe.
            </p>
            @endif

            <div class="btn-wrap">
                <a href="{{ $resetUrl }}" class="btn">Définir mon nouveau mot de passe</a>
            </div>

            <p style="font-size:13px;color:#78716c;">Si le bouton ne fonctionne pas, copiez ce lien dans votre navigateur :</p>
            <div class="url-box">{{ $resetUrl }}</div>

            <div class="warning">
                <strong>⏱ Ce lien expire dans 30 minutes.</strong>
                Il ne peut être utilisé qu'une seule fois. Si vous ne l'utilisez pas à temps,
                demandez à un super administrateur de vous en envoyer un nouveau.
            </div>
        </div>
        <div class="footer">
            <p>Si vous n'attendiez pas cet e-mail, ignorez-le. Votre mot de passe reste inchangé.</p>
        </div>
    </div>
</div>
</body>
</html>
