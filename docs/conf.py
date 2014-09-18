import sys, os
import sphinx_rtd_theme
from sphinx.highlighting import lexers
from pygments.lexers.web import PhpLexer


lexers['php'] = PhpLexer(startinline=True, linenos=1)
lexers['php-annotations'] = PhpLexer(startinline=True, linenos=1)
primary_domain = 'php'

extensions = []
templates_path = ['_templates']
source_suffix = '.rst'
master_doc = 'index'
project = u'Guzzle Ring'
copyright = u'2014, Michael Dowling'
version = '1.0.0-alpha'
exclude_patterns = ['_build']

html_title = "Guzzle Ring"
html_short_title = "Guzzle Ring"
html_theme = "sphinx_rtd_theme"
html_theme_path = [sphinx_rtd_theme.get_html_theme_path()]
