// Configuración de Tailwind (sistema "Liquid Glass" de docs/SKILL-ui.md).
// Compilar: ./scripts/css.sh  (o automáticamente al construir la imagen Docker).
module.exports = {
  darkMode: 'class',
  content: ['./app/**/*.php', './publico/js/**/*.js'],
  theme: {
    extend: {
      colors: {
        accent: 'var(--accent)',
        'accent-strong': 'var(--accent-strong)',
        'accent-soft': 'var(--accent-soft)',
        fg: 'var(--text)',
        muted: 'var(--text-muted)',
        subtle: 'var(--text-subtle)',
        success: 'var(--success)',
        warning: 'var(--warning)',
        danger: 'var(--danger)',
        info: 'var(--info)',
      },
      borderRadius: { glass: '24px' },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
        display: ['Oswald', 'Inter', 'sans-serif'],
      },
      boxShadow: { glass: 'var(--glass-shadow)', glow: 'var(--accent-glow)' },
    },
  },
  plugins: [],
};
