## Sub-course
echo "Cloning Sub-course Module from origin"
(cd .. && git clone https://github.com/HarcourtsAcademy/moodle-mod_subcourse.git mod/subcourse)
(cd .. && git --git-dir=mod/subcourse/.git remote add upstream https://github.com/mudrd8mz/moodle-mod_subcourse.git)
(cd .. && echo /mod/subcourse/ >> .git/info/exclude)
