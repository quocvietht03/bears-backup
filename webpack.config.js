var path = require('path');

module.exports = {
  context: __dirname,
  devtool: 'source-map',
  entry: {
    'bears-backup': __dirname + '/assets/js/bears-backup.js',
  },
  output: {
    path: path.resolve(__dirname, 'assets', 'js'),
    filename: '[name].bundle.js',
  },
  module: {
    rules: []
  },
  plugins: []
}
