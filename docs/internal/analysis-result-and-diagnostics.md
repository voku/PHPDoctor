# AnalysisResult and diagnostics

## Purpose

`AnalysisResult` is the internal analysis contract after the analysis cleanup. New internal analysis work should target this model instead of introducing parallel result shapes.

## Data model

`AnalysisResult` keeps two sources separate:

- `diagnostics()` returns the typed `DiagnosticCollection`. This is the source of truth for checks that have been migrated.
- `legacyOnlyErrors()` returns only legacy string messages for checks that have not been migrated yet.

This separation is intentional. A migrated check must emit `Diagnostic` objects, and its migrated messages must not also remain in `legacyOnlyErrors()`.

## Compatibility projection

`toLegacyErrors()` is a compatibility projection. It combines:

- `legacyOnlyErrors()` as-is
- `diagnostics()` mapped back to legacy text

Use this only for text output and legacy wrappers that still expose array-based errors.

## Findings mapping

`findings()` maps each source explicitly:

- `legacyOnlyErrors()` through `Finding::fromMessage()`
- `diagnostics()` through `DiagnosticToFindingMapper::map()`

This keeps finding generation aligned with the real source of each issue instead of recreating a mixed storage model.

## API guidance

Prefer the typed APIs:

- `analyseString()`
- `analyseFiles()`

The following are legacy array-returning wrappers:

- `checkFromString()`
- `checkPhpFiles()`

Profile and baseline generation should consume `AnalysisResult`.

## Migration rules

When migrating checks to diagnostics:

1. Emit `Diagnostic` objects for the migrated check.
2. Remove the migrated messages from `legacyOnlyErrors()`.
3. Keep `toLegacyErrors()` as the compatibility layer for legacy output.
4. Do not add new `errors + diagnostics` dual-flow APIs.

The target model is a single internal analysis contract with compatibility projections at the edges, not a reintroduction of the removed dual-flow architecture.
