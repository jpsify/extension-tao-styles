# extension-tao-styles

Customize the appearance of the TAO platform

### Compile themes

To compiles the themes, you will need the [`theme-toolkit`](https://github.com/oat-sa/theme-toolkit).

In the `profiles.json` file, at root of the tool's folder, add these lines, replacing `<PATH_TO_YOUR_PROJECT>` by the path to your project root:

```JSON
"multi": {
    "src": "<PATH_TO_YOUR_PROJECT>/taoStyles/themes/scss/themes",
    "dest": "<PATH_TO_YOUR_PROJECT>/taoStyles/views/css/themes"
}
```

From the root of the `theme-toolkit`, run this command to compile the themes:
`grunt compile -p=multi`
