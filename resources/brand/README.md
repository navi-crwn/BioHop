# Brand images

`logo.svg` (wordmark) and `favicon.svg` are served by `BrandAssetController` at `/brand/{version}/{file}`;
`route('brand.logo')`, `route('brand.favicon')` and `route('brand.asset', 'file.svg')` make the links, with a
version that changes whenever a file here changes. Replace the placeholder SVGs with the real BioHop brand.
