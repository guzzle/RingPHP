import sys, os
from sphinx.highlighting import lexers
from pygments.lexers.web import PhpLexer

lexers['php'] = PhpLexer(startinline=True, linenos=1)
lexers['php-annotations'] = PhpLexer(startinline=True, linenos=1)
primary_domain = 'php'

# -- General configuration -----------------------------------------------------

extensions = []
templates_path = ['_templates']
source_suffix = '.rst'
master_doc = 'index'

project = u'Guzzle Ring'
copyright = u'2014, Michael Dowling'
version = '1.0.0-alpha'
exclude_patterns = ['_build']

# -- Options for HTML output ---------------------------------------------------

html_title = "Guzzle Ring"
html_short_title = "Guzzle Ring"
html_static_path = ['_static']
html_sidebars = {
    '**':       ['localtoc.html', 'searchbox.html']
}

# -- Guzzle Sphinx theme setup ------------------------------------------------

sys.path.insert(0, '/Users/dowling/projects/guzzle_sphinx_theme')

import guzzle_sphinx_theme
html_translator_class = 'guzzle_sphinx_theme.HTMLTranslator'
html_theme_path = guzzle_sphinx_theme.html_theme_path()
html_theme = 'guzzle_sphinx_theme'

# Guzzle theme options (see theme.conf for more information)
html_theme_options = {
    "project_nav_name": "Guzzle Ring",
    "google_analytics_account": "UA-22752917-1"
}
