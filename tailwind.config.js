import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
    ],

    theme: {
        extend: {
            colors: {
                ink: "#0B1220",
                paper: "#F7F6F3",
                panel: "#FFFFFF",
                line: "#E4E2DC",
                muted: "#6B7280",
                accent: { DEFAULT: "#FF4B2B", dark: "#E0391C" },
                cobalt: { DEFAULT: "#2547E0", dark: "#1B36AF" },
                good: "#16A34A",
                warn: "#F5A524",
                // Admin redesign surface tokens (dark glass dashboard)
                surface: {
                    base: "#0A0D14",
                    sidebar: "#0D111C",
                    card: "#121726",
                    elevated: "#171E31",
                    border: "rgba(255, 255, 255, 0.07)",
                    borderHover: "rgba(99, 102, 241, 0.35)",
                },
                // Landing page redesign — dark athletic palette
                obsidian: {
                    950: "#040711",
                    900: "#070a12",
                    850: "#0b111e",
                    800: "#0e172a",
                    700: "#1e293b",
                    600: "#334155",
                },
                electric: {
                    400: "#38bdf8",
                    500: "#0ea5e9",
                    600: "#2563eb",
                    700: "#1d4ed8",
                },
                neonCyan: "#06b6d4",
                neonAmber: "#f59e0b",
                neonGreen: "#10b981",
            },
            boxShadow: {
                glowCyan: "0 0 35px -5px rgba(6, 182, 212, 0.35)",
                glowBlue: "0 0 45px -5px rgba(37, 99, 235, 0.4)",
            },
            fontFamily: {
                // was "Figtree" — the page actually loads & uses Inter via Google Fonts
                sans: ["Inter", ...defaultTheme.fontFamily.sans],
                display: ['"Bebas Neue"', ...defaultTheme.fontFamily.sans],
                mono: ['"IBM Plex Mono"', ...defaultTheme.fontFamily.mono],
                // Admin redesign — matches the Stitch AI reference dashboard
                jakarta: ['"Plus Jakarta Sans"', ...defaultTheme.fontFamily.sans],
            },
        },
    },

    plugins: [forms],
};