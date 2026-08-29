import forms from '@tailwindcss/forms';
import containerQueries from '@tailwindcss/container-queries';

/** @type {import('tailwindcss').Config} */
export default {
  darkMode: "class",
  content: [
    "./resources/**/*.blade.php",
    "./resources/**/*.js",
    "./resources/**/*.vue",
    "./app/**/*.php",
  ],
  theme: {
    extend: {
      colors: {
        /* === Google Stitch 設計系統：主要品牌色（Primary / Teal）== */
        "primary": {
          "DEFAULT": "#006b5f",
          "container": "#14b8a6",
          "10": "#006b5f1a",
          "15": "#006b5f26",
          "20": "#006b5f33",
          "fixed": "#71f8e4",
          "fixed-dim": "#4fdbc8",
        },
        "on-primary": "#ffffff",
        "on-primary-container": "#00423b",
        "on-primary-fixed": "#00201c",
        "on-primary-fixed-variant": "#005048",
        "inverse-primary": "#4fdbc8",

        /* === Secondary 海綠/青藍（次要色）== */
        "secondary": {
          "DEFAULT": "#006a61",
          "container": "#86f2e4",
          "fixed": "#89f5e7",
          "fixed-dim": "#6bd8cb",
        },
        "on-secondary": "#ffffff",
        "on-secondary-container": "#006f66",
        "on-secondary-fixed": "#00201d",
        "on-secondary-fixed-variant": "#005049",

        /* === Tertiary 第三品牌色（青綠，比 Secondary 更亮）== */
        "tertiary": {
          "DEFAULT": "#006b5e",
          "container": "#09b8a4",
          "fixed": "#6ef9e2",
          "fixed-dim": "#4ddcc6",
        },
        "on-tertiary": "#ffffff",
        "on-tertiary-container": "#00423a",
        "on-tertiary-fixed": "#00201b",
        "on-tertiary-fixed-variant": "#005047",

        /* === Surface 表面色階（動態深淺色適應）== */
        "surface": {
          "DEFAULT": "rgb(var(--surface-rgb) / <alpha-value>)",
          "bright": "rgb(var(--surface-bright-rgb) / <alpha-value>)",
          "dim": "rgb(var(--surface-dim-rgb) / <alpha-value>)",
          "pure": "rgb(var(--surface-pure-rgb) / <alpha-value>)",
          "container": {
            "DEFAULT": "rgb(var(--surface-container-rgb) / <alpha-value>)",
            "low": "rgb(var(--surface-container-low-rgb) / <alpha-value>)",
            "high": "rgb(var(--surface-container-high-rgb) / <alpha-value>)",
            "highest": "rgb(var(--surface-container-highest-rgb) / <alpha-value>)",
            "lowest": "rgb(var(--surface-container-lowest-rgb) / <alpha-value>)",
          },
          "variant": "rgb(var(--surface-variant-rgb) / <alpha-value>)",
        },
        "on-surface": {
          "DEFAULT": "rgb(var(--on-surface-rgb) / <alpha-value>)",
          "variant": "rgb(var(--on-surface-variant-rgb) / <alpha-value>)",
          "50": "rgb(var(--on-surface-rgb) / 0.5)",
        },
        "inverse-surface": "rgb(var(--inverse-surface-rgb) / <alpha-value>)",
        "inverse-on-surface": "rgb(var(--inverse-on-surface-rgb) / <alpha-value>)",
        "outline": "rgb(var(--outline-rgb) / <alpha-value>)",
        "outline-variant": "rgb(var(--outline-variant-rgb) / <alpha-value>)",
        "surface-tint": "#006b5f",

        /* === 背景層 === */
        "background": "rgb(var(--background-rgb) / <alpha-value>)",
        "on-background": "rgb(var(--on-background-rgb) / <alpha-value>)",
        "background-warm": "rgb(var(--background-warm-rgb) / <alpha-value>)",
        "background-dim": "rgb(var(--background-dim-rgb) / <alpha-value>)",
        "border-base": "rgb(var(--border-base-rgb) / <alpha-value>)",
        "text-primary": "rgb(var(--text-primary-rgb) / <alpha-value>)",

        /* === 狀態色（success / danger / warning / error）== */
        "success": {
          "DEFAULT": "#10b981",
          "container": "#d1fae5",
          "15": "#10b98126",
          "20": "#10b98133",
        },
        "danger": {
          "DEFAULT": "#ef4444",
          "container": "#fee2e2",
          "15": "#ef444426",
          "20": "#ef444433",
        },
        "warning": {
          "DEFAULT": "#f59e0b",
          "container": "#fef3c7",
          "15": "#f59e0b26",
          "20": "#f59e0b33",
        },
        "error": {
          "DEFAULT": "#ba1a1a",
          "container": "#ffdad6",
        },
        "on-error": "#ffffff",
        "on-error-container": "#93000a",

        /* === Google Stitch 8 色分類色票 === */
        "category-amber":    "#FBBF24",
        "category-rose":     "#FB7185",
        "category-pink":     "#F472B6",
        "category-sky":      "#60A5FA",
        "category-mint":     "#34D399",
        "category-lavender": "#A78BFA",
        "category-orange":   "#F97316",
        "category-slate":    "#94A3B8",
      },
      borderRadius: {
        "sm": "0.125rem",
        "DEFAULT": "0.25rem",
        "md": "0.375rem",
        "lg": "0.5rem",
        "xl": "0.75rem",
        "full": "9999px",
      },
      spacing: {
        "card-padding": "20px",
        "grid-gap": "24px",
        "section-margin": "48px",
        "touch-target": "44px",
        "touch-target-child": "56px",
      },
      fontFamily: {
        "sans": ["Microsoft JhengHei", "system-ui", "sans-serif"],
        "display-md": ["Microsoft JhengHei"],
        "headline-lg": ["Microsoft JhengHei"],
        "headline-md": ["Microsoft JhengHei"],
        "body-lg": ["Microsoft JhengHei"],
        "body-md": ["Microsoft JhengHei"],
        "label-md": ["Microsoft JhengHei"],
        "amount-lg": ["Microsoft JhengHei"],
        "amount-sm": ["Microsoft JhengHei"],
      }
    }
  },
  plugins: [
    forms,
    containerQueries,
  ],
}
