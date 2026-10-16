# Real GitHub Actions evidence

Workflow: https://github.com/Jqlyu/faker-testing-project/actions/runs/38953376535

Commit: `4595ec4616e25bd364bea0a90b29bc64ed7f1393`. All three OS jobs and comparison completed successfully. Downloaded ZIP SHA256 values match the GitHub artifact digests in download-manifest.json. Expanded report runs are archived as their original run IDs in the parent folder. No local Windows or local Linux run is inferred.

`original-comparison/` preserves the unmodified CI comparison. `recomparison/` is the local revalidation of all supplied current archived evidence, with its actual source paths. Setup logs and infrastructure JUnit are separate from the 24 report cases. Raw Windows PHP OS text and runner image identifiers are preserved.
