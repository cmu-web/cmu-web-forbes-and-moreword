# Contributing

For setup and checks, see [Development](DEVELOPMENT.md).

## Report a Problem

Check existing issues first, then
[report a problem](https://github.com/cmu-web/cmu-web-forbes-and-moreword/issues/new?template=bug_report.yml).
Include your WordPress and PHP versions and the block or page.

## Submit a Pull Request

- Branch from `0.x` and open the pull request against `0.x`.
- Keep it to one concern.
- Explain the problem, the resulting behavior and how you checked the change.
- With the local site running, run `dev/run.sh check` before submitting (it
  needs only Docker; see [Check a Change](DEVELOPMENT.md#check-a-change)). The
  Check Code workflow runs it again on the pull request, with the browser
  tests, on two WordPress versions.

## Code and Writing Style

- Follow the Web Kilt's [writing
  conventions](https://webkilt.andrew.cmu.edu/0/conventions.html) for
  documentation and the docs site: AP style, and Title Case for headings,
  buttons and labels.
- Use made-up content in examples, and Andrew Carnegie (ac00) for a person.
- Use [Conventional Commits](https://www.conventionalcommits.org/en/v1.0.0/)
  for commit subjects.

## CMU's Names and Marks

CMU's names, marks and artwork aren't covered by the license. Use them only
as the project already does.
