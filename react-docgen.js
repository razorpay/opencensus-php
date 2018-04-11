let componentPath = process.argv[2];
if (!componentPath) {
  console.info('Usage: node react-docgen.js <path-to-component>');
  process.exit(1);
}

const path = require('path');
const fs = require('fs');
const reactDocgen = require('react-docgen');
const ReactDocGenMarkdownRenderer = require('react-docgen-markdown-renderer');
const renderer = new ReactDocGenMarkdownRenderer();

fs.readFile(componentPath, (error, content) => {
  const documentationPath =
    path.basename(componentPath, path.extname(componentPath)) +
    renderer.extension;
  const doc = reactDocgen.parse(content);

  let rendered = renderer.render(
    /* The path to the component, used for linking to the file. */
    componentPath,
    /* The actual react-docgen AST */
    doc,
    /* Array of component ASTs that this component composes*/
    []
  );

  console.log(rendered);
});
