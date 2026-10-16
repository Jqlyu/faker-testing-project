# Real GitHub Actions evidence

Workflow: https://github.com/Jqlyu/faker-testing-project/actions/runs/38954165253

Commit: `9178ced170507cc49b6d20f274996abbf1cf7a1f`. Three OS jobs and comparison completed successfully. All four downloaded ZIP SHA256 values match GitHub artifact digests. Original report run folders are archived by run ID in the parent directory. Setup logs and infrastructure JUnit are distinct from the24 report cases.

`original-comparison/` preserves CI output. `recomparison/` locally verifies an explicitly selected current set of three CI runs plus the local macOS full run; older/duplicate attempts are deliberately outside that input. `local-device-comparison/` correctly remains Not Run because native Windows/Linux are missing.

The current test contract hash is the same across all delivered current report runs: `bce51e785ade59283ea5338a72c95cc26e864cbd000fa8a00e4ba3a18f36028c`. Finder/Explorer metadata no longer affects test identity; Faker SUT source hashing remains strict. All earlier archived attempts are unchanged.

`intentional-oom-check/` is an explicitly labelled failure-path check with a deliberately invalid2M limit: exit255, native Allowed memory size error, no measurement JSON published. It is not a production N02 measurement or a performance criterion.
