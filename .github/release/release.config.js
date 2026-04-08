/**
 * @type {import('semantic-release').GlobalConfig}
 */
export default {
  branches: ['main', 'master'],
  plugins: [
    "@semantic-release/commit-analyzer",
    "@semantic-release/release-notes-generator",
    [
      "@semantic-release/wordpress",
      {
        "type": "plugin",
        "slug": "dnt-notify",
        "path": "../../",
        "releasePath": "../../"
      }
    ],
    [
      "@semantic-release/github",
      {
        "assets": [
          {
            "path": "../../package.zip",
            "name": "dnt-notify.zip",
            "label": "Plugin ZIP"
          }
        ]
      }
    ]
  ]
};
