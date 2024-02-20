/**
 * Function to remove deprecated specifiers from an import statement while ensuring correct formatting.
 * This function checks if all specifiers in an import statement are deprecated and either removes the entire statement
 * or selectively removes only the deprecated specifiers. It also adjusts commas as necessary to maintain the correct syntax.
 *
 * @param {ASTNode} node - The ImportDeclaration node being modified. Represents the import statement in the AST.
 * @param {SourceCode} sourceCode - The ESLint SourceCode object, used to access the text and tokens related to the AST nodes.
 * @param {ASTNode[]} deprecatedSpecifiers - An array of ImportSpecifier nodes marked as deprecated and to be removed.
 * @param {RuleFixer} fixer - The fixer utility provided by ESLint, used to apply changes to the code.
 * @returns {RuleFix | RuleFix[] | null} A single fix, an array of fixes to be applied by ESLint, or null if no action is necessary.
 */
const deprecatedSpecifiersFixer = (node, sourceCode, deprecatedSpecifiers, fixer) => {
  if (deprecatedSpecifiers.length === 0) {
    return null; // No deprecated specifiers, no action required.
  }

  if (node.specifiers.length === deprecatedSpecifiers.length) {
    // All specifiers are deprecated; remove the entire import statement.
    return fixer.remove(node);
  }

  // Only some specifiers are deprecated. Remove them individually and adjust commas.
  return deprecatedSpecifiers.map((specifier) => {
    const isLastSpecifier = node.specifiers.indexOf(specifier) === node.specifiers.length - 1;
    let textToRemove = sourceCode.getText(specifier);

    if (!isLastSpecifier) {
      const nextToken = sourceCode.getTokenAfter(specifier);
      if (nextToken && nextToken.value === ',') {
        textToRemove += ','; // Remove following comma with the specifier.
      }
    } else {
      const prevToken = sourceCode.getTokenBefore(specifier);
      if (prevToken && prevToken.value === ',') {
        textToRemove = ',' + textToRemove; // Remove preceding comma with the specifier.
      }
    }

    return fixer.removeRange([
      specifier.range[0] - (textToRemove[0] === ',' ? 1 : 0),
      specifier.range[1] + (textToRemove.endsWith(',') ? 1 : 0),
    ]);
  });
};

/**
 * Ensures that specific imports are present from a given package. If the import statement is missing, it adds it.
 * If the import statement exists but does not include the specified imports, it updates the statement accordingly.
 *
 * @param {RuleFixer} fixer - The fixer utility provided by ESLint.
 * @param {SourceCode} sourceCode - The ESLint SourceCode object, providing access to the code's AST and text.
 * @param {string} packageName - The name of the package from which the imports should come.
 * @param {string[]} importsToEnsure - The names of imports to ensure are present from the specified package.
 * @returns {RuleFix | null} A fix to be applied by ESLint, or null if no fix is needed.
 */
function ensureImportsFromPackage(fixer, sourceCode, packageName, importsToEnsure) {
  const importDeclaration = sourceCode.ast.body.find(
    (node) => node.type === 'ImportDeclaration' && node.source.value === packageName,
  );

  if (!importDeclaration) {
    // Import statement for the package is missing; add it with the specified imports.
    const importStatement = `import { ${importsToEnsure.join(', ')} } from '${packageName}';\n`;
    return fixer.insertTextBefore(sourceCode.ast.body[0], importStatement);
  }

  // Import statement exists. Update it to include missing specified imports.
  const existingImports = importDeclaration.specifiers
    .filter((specifier) => specifier.type === 'ImportSpecifier')
    .map((specifier) => specifier.imported.name);
  const missingImports = importsToEnsure.filter(
    (importName) => !existingImports.includes(importName),
  );

  if (missingImports.length > 0) {
    const newImportsList = [...existingImports, ...missingImports].join(', ');
    const newImportStatement = `import { ${newImportsList} } from '${packageName}';\n`;
    return fixer.replaceText(importDeclaration, newImportStatement);
  }

  return null; // All specified imports are already present; no action required.
}

module.exports = {
  deprecatedSpecifiersFixer,
  ensureImportsFromPackage,
};
