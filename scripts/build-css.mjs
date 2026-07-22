import { readFile, writeFile } from "node:fs/promises";
import { dirname, join, relative } from "node:path";
import { fileURLToPath } from "node:url";

const projectRoot = join(dirname(fileURLToPath(import.meta.url)), "..");
const sourceDirectory = join(projectRoot, "css", "src");
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
    ["170-admin-controls.css", null],
    ["180-admin-minimal.css", null],
    ["190-admin-modals.css", null],
    ["200-admin-invoices.css", null],
    ["210-typography.css", null],
    ["220-fitosanitario.css", null],
    ["230-admin-dashboard-minimal.css", null],
];

const moduleMap = new Map(modules);
const bundles = {
    "dashboard.css": modules.map(([fileName]) => fileName),
    "admin.css": [
        "00-foundation.css",
        "20-admin.css",
        "40-interactions-theme.css",
        "60-modals.css",
        "70-reports.css",
        "130-purchase-invoice.css",
        "160-role-actions.css",
        "170-admin-controls.css",
        "180-admin-minimal.css",
        "190-admin-modals.css",
        "200-admin-invoices.css",
        "210-typography.css",
        "220-fitosanitario.css",
        "230-admin-dashboard-minimal.css",
    ],
    "farmer.css": [
        "00-foundation.css",
        "10-farmer.css",
        "40-interactions-theme.css",
        "60-modals.css",
        "110-request-history.css",
        "160-role-actions.css",
        "210-typography.css",
        "220-fitosanitario.css",
    ],
    "warehouse.css": [
        "00-foundation.css",
        "40-interactions-theme.css",
        "50-warehouse.css",
        "60-modals.css",
        "100-warehouse-reports.css",
        "130-purchase-invoice.css",
        "160-role-actions.css",
        "210-typography.css",
        "220-fitosanitario.css",
    ],
    "public.css": [
        "00-foundation.css",
        "30-public-home.css",
        "40-interactions-theme.css",
        "90-public-inner.css",
        "120-public.css",
        "140-compatibility.css",
        "150-home-hero.css",
        "210-typography.css",
    ],
    "auth.css": [
        "00-foundation.css",
        "40-interactions-theme.css",
        "80-auth.css",
        "140-compatibility.css",
        "210-typography.css",
    ],
};

function normalizeLineEndings(content) {
    return content.replace(/\r\n?/g, "\n").trimEnd() + "\n";
}

async function buildBundle(bundleName, moduleNames) {
    const sections = [];

    for (const fileName of moduleNames) {
        if (!moduleMap.has(fileName)) {
            throw new Error(`Modulo CSS desconocido: ${fileName}`);
        }
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

    const bundlePath = join(projectRoot, "css", bundleName);
    await writeFile(bundlePath, banner + sections.join("\n"), "utf8");
    return bundlePath;
}

const builtBundles = [];
for (const [bundleName, moduleNames] of Object.entries(bundles)) {
    builtBundles.push(await buildBundle(bundleName, moduleNames));
}

console.log(
    `CSS compilado: ${builtBundles.map(path => relative(projectRoot, path)).join(", ")}.`,
);
