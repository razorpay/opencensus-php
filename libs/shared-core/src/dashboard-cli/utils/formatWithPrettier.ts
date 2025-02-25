import prettier from 'prettier';

/**
 * Formats the given content using Prettier.
 * @param content The content to format.
 * @param filePath The file path of the content.
 * @returns The formatted content.
 */
export const formatWithPrettier = async (content: string, filePath: string): Promise<string> => {
  try {
    return await prettier.format(content, {
      filepath: filePath,
      printWidth: 100,
      singleQuote: true,
      trailingComma: 'all',
      overrides: [
        {
          files: ['*.yml', '*.yaml'],
          options: {
            singleQuote: false,
          },
        },
      ],
    });
  } catch (error) {
    return content;
  }
};
