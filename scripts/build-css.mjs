import { stat } from "node:fs/promises";
import { dirname, join, relative } from "node:path";
import { fileURLToPath } from "node:url";

const projectRoot = join(dirname(fileURLToPath(import.meta.url)), "..");
const bundles = ["admin.css", "auth.css", "public.css", "material-icons.css"];

const verified = [];
for (const bundle of bundles) {
    const bundlePath = join(projectRoot, "css", bundle);
    const info = await stat(bundlePath);
    if (!info.isFile() || info.size === 0) {
        throw new Error(`Bundle CSS inválido: ${bundle}`);
    }
    verified.push(relative(projectRoot, bundlePath));
}

console.log(`CSS verificado: ${verified.join(", ")}.`);
