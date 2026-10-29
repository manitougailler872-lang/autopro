self.addEventListener('push', event => {
    if (!event.data) {
        return;
    }
    const data = event.data.json();
    const notificationId = data.notificationId;
    const notificationOptions = {
        body: data.body,
        icon: '/icons/icon-192.png',
        badge: '/icons/badge-72.png',
        data: {
            notificationId: notificationId
        }
    };
    event.waitUntil(
        self.registration
            .showNotification(data.title, notificationOptions)
            .then(() => {
                // Notification affichée par le Service Worker
                return fetch('/api/push/ack.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        notificationId: notificationId,
                        status: 'DELIVERED'
                    })
                });
            })
    );
});

self.addEventListener('notificationclick', event => {
    event.notification.close();
    const notificationId = event.notification.data.notificationId;
    event.waitUntil(
        fetch('/api/push/ack.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                notificationId: notificationId,
                status: 'CLICKED'
            })
        })
    );
});