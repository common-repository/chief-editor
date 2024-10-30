# -*- coding: utf-8 -*-
import pip;
import pkg_resources;

try: # for pip >= 10
    from pip._internal.utils.misc import get_installed_distributions
    #print "pip >10+"
    installed_packages = get_installed_distributions();
except ImportError: # for pip <= 9.0.3
    from pip import get_installed_distributions
    installed_packages = pip.get_installed_distributions();

print(installed_packages);  