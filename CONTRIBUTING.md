# Contribution Guidelines

1. Make sure that the Dashboard is setup properly locally.
2. Make sure you have tested all your changes locally.
3. Ensure that tests pass
4. If you have made a UI change, please add screenshots of all affected screens on the PR
5. If you have made any Javascript changes, ensure that:
  - You have prettier setup and working.
  - See the README for this
6. If you have touched any class/function, please make sure that it reads like *Modern PHP*.
This means:
  - Use square bracket arrays. If a code you are touching has `array()`, convert it to `[]`.
  - Use class constants whever possible, instead of static variables.
  - Use typehints in your code. Since we use PHP 7, we have scalar typehints, so you can use `string, int, bool, float` as typehints.
  - Use [return type declarations](https://wiki.php.net/rfc/return_types "PHP RFC: Return Types")
  - Use [the Null Coalesce Operator](https://wiki.php.net/rfc/isset_ternary) instead of isset + ternary (and other variants).
  - Use a third-party package (or consider packaging your code as well) if it fits.
7. All commit messages must include a `[module name]` as the prefix. This could be something
like `[payment], [merchant], [settlement], [ci], [style], [react], [deps]`. Just ensure it is there.

In all cases, try to _write clean code_. This means:

- No magic strings.
- No arcane one-liners.
- Err on the side of over-commenting, rather than under.
- Leave helpful notes in `HACKING.md` for others to follow various flows.
- Follow the [Boy Scout Rule](http://wiki.c2.com/?BoyScoutRule):

>Leave the campground cleaner than you found it.  
>Leave the code cleaner than you found it.  
>Clean up code as you go and develop more code.  
>If you don't you will find yourself in a mess shortly (much more likely than for camping sites).

If you are touching a piece of code, think long-term and try to clean up
and improve the code. If you see a style violation in the same class
you are working on, go ahead and fix it.

Your future self will thank you.

The PR template should help you with some of this.
