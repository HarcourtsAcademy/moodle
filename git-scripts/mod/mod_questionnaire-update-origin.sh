#!/bin/bash
echo "Updating Questionnaire Module from Origin"
(cd ../mod/questionnaire/ && git fetch origin)
(cd ../mod/questionnaire/ && git checkout HA-Moodle28)