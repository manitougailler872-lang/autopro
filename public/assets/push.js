// push.js

const VAPID_PUBLIC_KEY = 'TA_CLE_VAPID_PUBLIQUE';
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);

    return Uint8Array.from([...rawData].map(char => char.charCodeAt(0)));
}

async function registerPush() {
    alert('ok');
    if (!('serviceWorker' in navigator)) {
        console.log('Service Worker non supporté');
        return;
    }
    if (!('PushManager' in window)) {
        console.log('Push API non supportée');
        return;
    }

    // Enregistrement du Service Worker
    const registration = await navigator.serviceWorker.register('/assets/sw.js');
    // Demande de permission
    const permission = await Notification.requestPermission();
    console.log(permission);

    if (permission !== 'granted') {
        console.log('Permission notification refusée');
        return;
    }

    // Récupérer une subscription existante
    let subscription = await registration.pushManager.getSubscription();

    // Si aucune subscription, en créer une
    if (!subscription) {
        subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(
                VAPID_PUBLIC_KEY
            )
        });
    }
    // Envoyer la subscription au serveur
    await saveSubscription(subscription);
    console.log('PushSubscription enregistrée');
}


async function saveSubscription(subscription) {
    console.log(subscription);
    const response = await fetch('/api/push/subscribe', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(subscription.toJSON())
    });

    if (!response.ok) {
        throw new Error('Impossible d’enregistrer la subscription');
    }

    return response.json();
}


registerPush().catch(console.error);
