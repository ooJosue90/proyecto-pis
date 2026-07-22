import { access } from "node:fs/promises";
import { dirname, join } from "node:path";
import { fileURLToPath } from "node:url";
import sharp from "sharp";

const imageDirectory = join(dirname(fileURLToPath(import.meta.url)), "..", "assets", "img");
const images = [
    ["about", 78],
    ["about2", 78],
    ["ada-avatar", 78],
    ["bg", 72],
    ["intro", 78],
    ["mango-empaque", 78],
    ["products-00", 78],
    ["products-01", 78],
    ["products-02", 78],
    ["products-03", 78],
];

for (const [name, quality] of images) {
    const source = join(imageDirectory, `${name}.jpg`);
    const output = join(imageDirectory, `${name}.webp`);
    await access(source);
    await sharp(source).webp({ quality, effort: 5 }).toFile(output);
}

console.log(`Imagenes optimizadas: ${images.length}`);
