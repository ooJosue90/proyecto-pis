import { readFile, writeFile } from "node:fs/promises";
import { dirname, join, relative } from "node:path";
import { fileURLToPath } from "node:url";

const projectRoot = join(dirname(fileURLToPath(import.meta.url)), "..");
const sourceDirectory = join(projectRoot, "css", "src");
const bundlePath = join(projectRoot, "css", "dashboard.css");

const modules = [
    ["00-foundation.css", null],
    ["10-farmer.css", "/* Agricultor dashboard */"],
    ["20-admin.css", "/* Admin dashboard */"],
    ["30-public-home.css", "/* Final home hero layout: grouped content, stable metrics and no cascade drift. */"],
    ["40-interactions-theme.css", "/* Motion system: restrained feedback for state, hierarchy and navigation. */"],
    ["50-warehouse.css", "/* Warehouse dashboard */"],
    ["60-modals.css", "/* Premium administrative modals */"],
    ["70-reports.css", "/* Premium system reports */"],
    ["80-auth.css", "/* Premium login experience */"],
    ["90-public-inner.css", "/* Premium product and about pages */"],
    ["100-warehouse-reports.css", "/* Warehouse report pages */"],
    ["110-request-history.css", "/* Farmer request history */"],
    ["120-public.css", "/* Public site */"],
    ["130-purchase-invoice.css", "/* Purchase invoice module */"],
    ["140-compatibility.css", "/* Language layout safeguards for public HTML pages. */"],
    ["150-home-hero.css", null],
    ["160-role-actions.css", null],
];

function normalizeLineEndings(content) {
    return content.replace(/\r\n?/g, "\n").trimEnd() + "\n";
}

async function buildBundle() {
    const sections = [];

    for (const [fileName] of modules) {
        const path = join(sourceDirectory, fileName);
        const content = normalizeLineEndings(await readFile(path, "utf8"));
        sections.push(`/* Source: css/src/${fileName} */\n${content}`);
    }

    const banner = [
        "/*",
        " * Archivo generado. No editar directamente.",
        " * Fuente: css/src/*.css",
        " * Compilar: node scripts/build-css.mjs",
        " */",
        "",
    ].join("\n");

    await writeFile(bundlePath, banner + sections.join("\n"), "utf8");
}

await buildBundle();

console.log(
    `CSS compilado: ${relative(projectRoot, bundlePath)} desde ${modules.length} modulos.`,
);
