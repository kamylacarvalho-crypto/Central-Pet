/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./html/**/*.html"], // Diz ao Tailwind para monitorar todos os arquivos HTML dentro da pasta html
  theme: {
    extend: {
      fontFamily: {
        sans: ['Roboto', 'sans-serif'],
        serif: ['"Abhaya Libre"', 'serif'],
      }
    },
  },
  plugins: [],
}