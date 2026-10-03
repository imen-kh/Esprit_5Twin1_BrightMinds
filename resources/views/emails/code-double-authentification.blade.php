{{-- Email du code de double authentification : styles en ligne, obligatoires pour les messageries --}}
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Votre code de vérification</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f6f8; font-family: Arial, Helvetica, sans-serif; color: #45595b;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f6f8; padding: 32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width: 560px; background-color: #ffffff; border-radius: 10px; overflow: hidden;">
                    <tr>
                        <td style="background-color: #81c408; padding: 24px 32px; text-align: center;">
                            <span style="font-size: 26px; font-weight: bold; color: #ffffff;">NutriTrace</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding: 32px;">
                            <h1 style="margin: 0 0 16px; font-size: 20px; color: #45595b;">Bonjour {{ $user->name }},</h1>
                            <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.6;">
                                Voici votre code de vérification pour terminer votre connexion à NutriTrace :
                            </p>
                            <p style="margin: 24px 0; text-align: center;">
                                <span style="display: inline-block; background-color: #f4f6f8; color: #45595b; font-size: 32px; font-weight: bold; letter-spacing: 8px; padding: 16px 28px; border-radius: 10px;">{{ $code }}</span>
                            </p>
                            <p style="margin: 0 0 16px; font-size: 15px; line-height: 1.6;">
                                Ce code expire dans {{ $duree }} minutes et ne peut servir qu'une seule fois.
                            </p>
                            <p style="margin: 0; font-size: 15px; line-height: 1.6;">
                                Si vous n'êtes pas à l'origine de cette connexion, ne communiquez ce code à personne et changez votre mot de passe.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color: #f4f6f8; padding: 16px 32px; text-align: center; font-size: 12px; color: #8a9a9c;">
                            NutriTrace — de la ferme à l'assiette
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
