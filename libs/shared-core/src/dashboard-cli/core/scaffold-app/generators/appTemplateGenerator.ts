import { formatWithPrettier } from '@src/dashboard-cli/utils/formatWithPrettier';
import { createDir } from '@src/scripts';
import fs from 'fs/promises';
import path from 'path';

/**
 * Recursively processes all .tpl files in the input directory,
 * applies placeholder replacements, and writes them to the output directory,
 * preserving the relative directory structure.
 *
 * Supports nested keys, e.g.:
 *   {{ integratedAppSentryConfig.project.value }}
 *
 * @param inputDir - The directory containing template files.
 * @param outputDir - The directory where processed files will be created.
 * @param ignoreList - An array of file or directory names to ignore.
 * @param replacements - A key-value map for placeholder replacements (can include nested objects).
 */
export const appTemplateGenerator = async (
  inputDir: string,
  outputDir: string,
  ignoreList: string[],
  replacements: Record<string, any>,
): Promise<void> => {
  createDir(outputDir);
  const entries = await fs.readdir(inputDir, { withFileTypes: true });

  // Helper to resolve nested keys (e.g., "integratedAppSentryConfig.project.value")
  const getValue = (obj: any, keyPath: string): any =>
    keyPath.split('.').reduce((acc, key) => (acc != null ? acc[key] : undefined), obj);

  // Regex to match placeholders like {{ key }} or {{ key.nested.key }}
  const placeholderRegex = /{{\s*([^}\s]+(?:\.[^}\s]+)*)\s*}}/g;

  for (const entry of entries) {
    // Skip ignored names
    if (ignoreList.includes(entry.name)) {
      continue;
    }

    const inputPath = path.join(inputDir, entry.name);
    const outputPath = path.join(outputDir, entry.name);

    if (entry.isDirectory()) {
      await fs.mkdir(outputPath, { recursive: true });
      await appTemplateGenerator(inputPath, outputPath, ignoreList, replacements);
    } else if (entry.isFile() && entry.name.endsWith('.tpl')) {
      const templateContent = await fs.readFile(inputPath, 'utf8');

      const craftedContent = templateContent.replace(placeholderRegex, (_, keyPath) => {
        const value = getValue(replacements, keyPath);
        return value !== undefined ? String(value) : '';
      });

      const outputFileName = entry.name.slice(0, -4);
      const finalOutputPath = path.join(outputDir, outputFileName);

      const formattedContent = await formatWithPrettier(
        craftedContent,
        finalOutputPath,
      );

      await fs.writeFile(finalOutputPath, formattedContent, 'utf8');
      console.log(`✅ ${outputFileName}`);
    }
  }
};
