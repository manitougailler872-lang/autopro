/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./templates/**/*.html.twig",
    "./assets/**/*.{js,css}",
    "./src/**/*.php",
  ],
  theme: {
    extend: {
      colors: {
        // ---- Fonds (clairs, alignés sur le blanc cassé du logo) ----
        // Noms conservés pour ne rien casser dans les templates existants
        "luxe-black": "#FDFDFD", // fond principal — blanc cassé exact du logo
        "luxe-black-alt": "#f5f7f9", // fond alterné (sections en rupture douce)
        "luxe-card": "#FFFFFF", // fond des cartes
        "luxe-border": "#E6E3DC", // bordures fines

        // ---- Accent de marque (rouge AutoPro, extrait du logo) ----
        "luxe-orange": "#ED1719", // rouge signature du logo
        "luxe-orange-dark": "#C81214", // variante foncée (hover, texte sur fond clair)
        "luxe-orange-soft": "rgba(237, 23, 25, 0.12)",
        // Alias conservés pour compatibilité (pointent désormais vers le rouge)
        "luxe-gold": "#ED1719",
        "luxe-gold-dark": "#C81214",

        "luxe-text": "#000000",
        "luxe-muted": "#555555",
        "luxe-faint": "#555555",
        //   'luxe-text':  '#000000',
        // 'luxe-muted': '#6B7280',
        // 'luxe-faint': '#9CA3AF',
        // ---- Vert "vérifié" (avis, badges de confiance) ----
        "luxe-green": "#FBBC04", // vert des étoiles / badges "Achat vérifié"
        "luxe-green-dark": "#008A44", // variante foncée (hover)
        "luxe-green-soft": "rgba(0, 166, 81, 0.12)", // fond léger pour badges
      },
      fontFamily: {
        sans: ['"Inter"', "Inter", "system-ui", "sans-serif"],
        display: ['"Inter"', "Inter", "system-ui", "sans-serif"],
        mono: [
          '"JetBrains Mono"',
          "ui-monospace",
          "SFMono-Regular",
          "monospace",
        ],
      },
      borderRadius: {
        xl: "0.9rem",
        "2xl": "1.25rem",
      },
      keyframes: {
        fadeIn: { "0%": { opacity: 0 }, "100%": { opacity: 1 } },
        fadeInUp: {
          "0%": { opacity: 0, transform: "translateY(20px)" },
          "100%": { opacity: 1, transform: "translateY(0)" },
        },
        confettiFall: {
          "0%": { transform: "translateY(-10vh) rotate(0deg)", opacity: 1 },
          "100%": { transform: "translateY(110vh) rotate(720deg)", opacity: 0 },
        },
        marquee: {
          "0%": { transform: "translateX(0%)" },
          "100%": { transform: "translateX(-50%)" },
        },
        "cta-pulse": {
          "0%, 100%": { boxShadow: "0 0 0 0 rgba(237, 23, 25, 0.45)" },
          "50%": { boxShadow: "0 0 0 12px rgba(237, 23, 25, 0)" },
        },
      },
      animation: {
        marquee: "marquee 22s linear infinite",
        "cta-pulse": "cta-pulse 2.2s ease-out infinite",
        "fade-in": "fadeIn .6s ease-out both",
        "fade-in-up": "fadeInUp .8s ease-out both",
        "confetti-fall": "confettiFall 2.5s linear infinite",
        "pulse-1s": "pulse 1s ease-in-out infinite",
      },
    },
  },
  plugins: [],
};