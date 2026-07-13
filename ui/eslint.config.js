import js from "@eslint/js";
import globals from "globals";
import pluginReact from "eslint-plugin-react";
import reactHooks from "eslint-plugin-react-hooks";
import json from "@eslint/json";
import css from "@eslint/css";
import { defineConfig } from "eslint/config";

export default defineConfig([
  {
    ignores: [
      "**/.oxlintrc.json",
      "eslint.config.js",
      "package.json",
      "package-lock.json",
      "vite.config.js",
      "dist/",
      "node_modules/",
    ],
  },
  {
    files: ["**/*.{js,mjs,cjs,jsx}"],
    plugins: { js },
    extends: ["js/recommended"],
    languageOptions: { globals: globals.browser },
  },
  {
    ...pluginReact.configs.flat.recommended,
    files: ["**/*.{js,jsx}"],
    settings: {
      react: {
        version: "detect",
      },
    },
  },
  {
    ...pluginReact.configs.flat["jsx-runtime"],
    files: ["**/*.{js,jsx}"],
  },
  {
    ...reactHooks.configs.flat.recommended,
    files: ["**/*.{js,jsx}"],
  },
  {
    files: ["**/*.json"],
    plugins: { json },
    language: "json/json",
    extends: ["json/recommended"],
  },
  {
    files: ["**/*.css"],
    plugins: { css },
    language: "css/css",
    extends: ["css/recommended"],
  },
]);
