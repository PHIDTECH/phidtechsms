/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
  ],
  theme: {
    extend: {
      colors: {
        primary: {
          50: '#f3f1ff',
          100: '#e9e5ff',
          200: '#d6cfff',
          300: '#b8a9ff',
          400: '#9478ff',
          500: '#6144f2',
          600: '#5a3de8',
          700: '#4d32d4',
          800: '#3f28b0',
          900: '#35238f',
        },
        brand: '#6144f2',
        sidebar: {
          bg: '#1f2937',
          hover: '#374151',
          text: '#d1d5db',
          active: '#6144f2',
        }
      },
      fontFamily: {
        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
      },
      keyframes: {
        'fade-in': {
          '0%': { opacity: '0' },
          '100%': { opacity: '1' }
        },
        'fade-in-up': {
          '0%': { opacity: '0', transform: 'translateY(20px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' }
        },
        'fade-in-right': {
          '0%': { opacity: '0', transform: 'translateX(-20px)' },
          '100%': { opacity: '1', transform: 'translateX(0)' }
        },
        'fade-in-left': {
          '0%': { opacity: '0', transform: 'translateX(20px)' },
          '100%': { opacity: '1', transform: 'translateX(0)' }
        },
        float: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-10px)' }
        },
        'scale-pulse': {
          '0%, 100%': { transform: 'scale(1)' },
          '50%': { transform: 'scale(1.05)' }
        }
      },
      animation: {
        'fade-in': 'fade-in 0.8s ease-out both',
        'fade-in-up': 'fade-in-up 0.8s ease-out both',
        'fade-in-right': 'fade-in-right 0.8s ease-out both',
        'fade-in-left': 'fade-in-left 0.8s ease-out both',
        float: 'float 6s ease-in-out infinite',
        'pulse-slow': 'scale-pulse 4s ease-in-out infinite'
      },
      boxShadow: {
        '3xl': '0 35px 60px -15px rgba(0,0,0,0.3)',
        glow: '0 10px 30px -10px rgba(99,102,241,0.45)'
      }
    },
  },
  plugins: [],
  corePlugins: {
    // Ensure all core plugins are enabled
  }
}