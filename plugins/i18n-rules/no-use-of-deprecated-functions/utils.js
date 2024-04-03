/**
 * Finds an import declaration for a specific package.
 */
function findImportDeclaration(sourceCode, packageName) {
  return sourceCode.ast.body.find(
    (node) => node.type === 'ImportDeclaration' && node.source.value === packageName,
  );
}

/**
 * Processes import declarations to remove deprecated specifiers and ensure required imports.
 *
 * @param {Object} options - Configuration and context for processing.
 * @param {SourceCode} options.sourceCode - ESLint's source code object.
 * @param {RuleFixer} options.fixer - ESLint's fixer utility.
 * @param {string} options.deprecatedSpecifiersFrom - The package name of the deprecated specifiers.
 * @param {string[]} options.deprecatedSpecifiers - The specifiers to be deprecated and removed.
 * @param {string} options.importPackageFrom - The package name to ensure is imported.
 * @param {string} options.specifierToImport - The specifiers to ensure are imported.
 * @returns {RuleFix[] | null} A list of fixes to be applied, or null if no fixes are necessary.
 */
function processImports({
  sourceCode,
  fixer,
  deprecatedSpecifiersFrom,
  deprecatedSpecifiers,
  importPackageFrom,
  specifierToImport,
  node,
}) {
  const fixes = [];
  if (node.specifiers.length === deprecatedSpecifiers.length) {
    fixes.push(fixer.remove(node));
  } else {
    if (deprecatedSpecifiersFrom !== importPackageFrom) {
      node.specifiers.forEach((specifier) => {
        if (deprecatedSpecifiers.includes((specifier.local || specifier.imported).name)) {
          let rangeStart = specifier.range[0];
          let rangeEnd = specifier.range[1];
          const nextToken = sourceCode.getTokenAfter(specifier);
          const prevToken = sourceCode.getTokenBefore(specifier);

          if (nextToken && nextToken.value === ',') {
            rangeEnd += 1;
          } else if (prevToken && prevToken.value === ',') {
            rangeStart -= 1;
          }

          fixes.push(fixer.removeRange([rangeStart, rangeEnd]));
        }
      });
    }
  }

  // Ensure or update required import declaration
  const importDeclarationToEnsure = findImportDeclaration(sourceCode, importPackageFrom);
  if (!importDeclarationToEnsure) {
    // If the import declaration doesn't exist, create and insert a new one
    const newImportStatement = `import { ${specifierToImport} } from '${importPackageFrom}';\n`;
    fixes.push(fixer.insertTextBefore(sourceCode.ast.body[0], newImportStatement));
  } else {
    // Update existing import declaration if it's missing specified imports
    const existingImports = importDeclarationToEnsure.specifiers
      .map((specifier) => (specifier.local || specifier.imported).name)
      // Filtering the deprecatedSpecifiers and specifierToImport from the existingImports
      .filter((specifier) => !deprecatedSpecifiers.includes(specifier) || !specifierToImport);

    const updatedImports = [...existingImports, specifierToImport].join(', ');
    const updatedImportStatement = `import { ${updatedImports} } from '${importPackageFrom}';\n`;
    fixes.push(fixer.replaceText(importDeclarationToEnsure, updatedImportStatement));
  }

  return fixes.length > 0 ? fixes : null;
}

const createConfigForDeprecatedFunction = ({
  deprecatedSpecifiers,
  deprecatedSpecifiersFrom,
  importPackageFrom,
  specifierToImport,
  message,
}) => {
  return {
    deprecatedSpecifiers,
    deprecatedSpecifiersFrom,
    importPackageFrom,
    specifierToImport,
    message:
      message ||
      `Use "${specifierToImport}" from "${importPackageFrom}" instead of "${deprecatedSpecifiers}" from "${deprecatedSpecifiersFrom}"`,
    processImports: (node, sourceCode, fixer) =>
      processImports({
        node,
        sourceCode,
        fixer,
        importPackageFrom,
        specifierToImport,
        deprecatedSpecifiers,
        deprecatedSpecifiersFrom,
      }),
  };
};

module.exports = {
  createConfigForDeprecatedFunction,
  findImportDeclaration,
};
