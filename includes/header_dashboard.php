<?php
require_once __DIR__ . '/session.php';
check_login();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AURA - Service Management System</title>
    <link rel="icon" type="image/png" href="<?= BASE_URL ?>images/logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="<?= BASE_URL ?>js/ui_helpers.js"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    <style>
        /* Increase global base size for better readability. 
           This scales up all Tailwind 'rem' classes proportionally. */
        html {
            font-size: 110%; /* Bumps base size from 16px to ~17.6px */
        }
        
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .dark .glass {
            background: rgba(15, 23, 42, 0.6);
            border-color: rgba(255, 255, 255, 0.05);
        }

        .light .glass {
            background: rgba(255, 255, 255, 0.8);
            border-color: rgba(15, 23, 42, 0.1);
        }

        /* Dropdown Option Styling for Dark Mode */
        .dark option {
            background-color: #0f172a; /* slate-900 */
            color: #f8fafc; /* slate-50 */
        }
        
        .light option {
            background-color: #ffffff;
            color: #0f172a; /* slate-900 */
        }

        .gradient-text {
            background: linear-gradient(135deg, #2dd4bf, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Smooth Page Transitions */
        .page-transition {
            animation: fadeIn 0.5s ease-out forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        body {
            transition: background-color 0.3s ease, color 0.3s ease;
        }
    </style>
    <script>
        // Theme initialization
        if (localStorage.getItem('theme') === 'light' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: light)').matches)) {
            document.documentElement.classList.add('light');
        } else {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>

<body class="bg-slate-50 dark:bg-[#0f172a] text-slate-800 dark:text-slate-200 min-h-screen">
    <div class="page-transition">
    
    <!-- Global Toast Trigger: reads ?msg= & ?type= from any redirect -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const p = new URLSearchParams(window.location.search);
            const msg  = p.get('msg');
            const type = p.get('type') || 'success';

            if (msg && typeof SMS_UI !== 'undefined') {
                // Correctly call showToast(message, type, duration)
                SMS_UI.showToast(decodeURIComponent(msg), type, 4000);

                // Clean URL without reloading
                const clean = new URL(window.location.href);
                clean.searchParams.delete('msg');
                clean.searchParams.delete('type');
                window.history.replaceState({}, '', clean.toString());
            }
        });
    </script>