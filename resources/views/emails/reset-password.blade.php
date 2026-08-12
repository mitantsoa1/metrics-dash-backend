@extends('emails.layouts.master')

@section('title', 'Réinitialisation de votre mot de passe - MetricsDash')

@section('content')
    <h1>Réinitialisation de votre mot de passe</h1>
    <p>Bonjour {{ $userName ?? 'Utilisateur' }},</p>
    <p>Vous recevez cet e-mail car nous avons reçu une demande de réinitialisation du mot de passe pour votre compte <strong>MetricsDash Enterprise</strong>.</p>
    
    <div class="btn-wrapper">
        <a href="{{ $resetUrl }}" class="btn" target="_blank">Réinitialiser mon mot de passe</a>
    </div>

    <p style="font-size: 13px; color: #64748b;">
        Ce lien de réinitialisation expirera dans <strong>60 minutes</strong>.<br />
        Si vous n'avez pas demandé de réinitialisation de mot de passe, aucune action supplémentaire n'est requise.
    </p>

    <hr style="border: none; border-top: 1px solid #e2e8f0; margin: 24px 0;" />

    <p style="font-size: 12px; color: #94a3b8; word-break: break-all;">
        Si vous rencontrez des difficultés en cliquant sur le bouton, copiez et collez l'URL ci-dessous dans votre navigateur web :<br />
        <a href="{{ $resetUrl }}" style="color: #151651;">{{ $resetUrl }}</a>
    </p>
@endsection
