import yaml
import re
import os

bin = os.path.dirname(os.path.realpath(__file__))
template = None

with open(bin + '/readme-template.yml', 'r') as file:
    template = yaml.safe_load(file)

def generate_md():
    with open(bin + '/../README.md', 'w+') as file:
        print('<!-- This file is generated by bin/generate-readme.py -->', file = file)
        print(file = file)
        
        print('# ' + template['name'], file = file)
        print(file = file)

        print(template['short-description'], file = file)
        print(file = file)

        print('## Description', file = file)
        print(file = file)

        for section in template['long-description']:
            if section['title']:
                print('### ' + section['title'], file = file)
                print(file = file)

            print(section['text'], file = file)

        print('## Installation', file = file)
        print(file = file)

        print(
            template['installation']
                .replace('{{php-min}}', template['php-min'])
                .replace('{{php-rec}}', template['php-rec']),
            file = file
        )

        print('## Screenshots', file = file)
        print(file = file)

        for (i, desc) in enumerate(template['screenshot-descriptions']):
            print('<p>', file = file)
            print('<img src="assets/screenshot-' + str(i + 1) + '.png" width="400px" alt="' + desc + '">', file = file)
            print('<br>' + desc, file = file)
            print('</p>', file = file)
            print(file = file)

        print('## Developing', file = file)
        print(file = file)

        for section in template['developing']:
            if section['title']:
                print('### ' + section['title'], file = file)
                print(file = file)

            print(section['text'], file = file)

        print(file = file)
        print('## Compatibility', file = file)
        print(file = file)

        print(
            template['compatibility']
                .replace('{{wp-min}}', template['wp-min'])
                .replace('{{php-min}}', template['php-min']),
            end = '',
            file = file
        )

        print(file = file)
        print('## Changelog', file = file)

        for entry in template['changelog']:
            print(file = file)
            print('### ' + entry['version'], file = file)
            print(file = file)
            print(entry['desc'], file = file)

def generate_txt():
    with open(bin + '/../readme.txt', 'w+') as file:
        print('=== ' + template['name'] + ' ===', file = file)
        print('Contributors: brandonxlf', file = file)
        print('Tags: woocommerce,file upload,checkout,order details', file = file)
        print('Donate link: https://www.brandonfowler.me/donate/', file = file)
        print('Requires at least: ' + template['wp-min'], file = file)
        print('Tested up to: ' + template['wp-tested'], file = file)
        print('Requires PHP: ' + template['php-min'], file = file)
        print('Stable tag: ' + template['version'], file = file)
        print('License: GPLv2 or later', file = file)
        print('License URI: https://www.gnu.org/licenses/gpl-2.0.html', file = file)
        print(file = file)

        print(template['short-description'], file = file)
        print(file = file)

        print('== Description ==', file = file)
        print(file = file)

        for section in template['long-description']:
            if section['title']:
                print('= ' + section['title'] + ' =', file = file)
                print(file = file)
                
            print(section['text'], file = file)

        print('== Installation ==', file = file)
        print(file = file)

        print(
            template['installation']
                .replace('{{php-min}}', template['php-min'])
                .replace('{{php-rec}}', template['php-rec']),
            file = file
        )

        print('== Screenshots ==', file = file)
        print(file = file)

        for (i, desc) in enumerate(template['screenshot-descriptions']):
            print(str(i + 1) + '. ' + desc, file = file)
        print(file = file)

        print('== Changelog ==', file = file)

        for entry in template['changelog']:
            print(file = file)
            print('= ' + entry['version'] + ' =', file = file)
            print(file = file)
            print(entry['desc'], file = file)

def generate_comment():
    out = '''/**
 * This comment is generated by bin/generate-readme.sh
 *
 * ''' + template['name'] + '''
 *
 * @package     Details and File Upload
 * @author      Brandon Fowler
 * @copyright   Brandon Fowler
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
'''
    out += ' * Plugin Name: ' + template['name'] + '\n'
    out += ' * Plugin URI: https://www.brandonfowler.me/details-and-file-upload/\n'
    out += ' * Description: Add general or item-specific detail inputs and file uploads to the WooCommerce checkout page.\n'
    out += ' * Version: ' + template['version'] + '\n'
    out += ' * Requires at least: ' + template['wp-min'] + '\n'
    out += ' * Requires PHP: ' + template['php-min'] + '\n'
    out += ' * Author: Brandon Fowler\n'
    out += ' * Author URI: https://www.brandonfowler.me/\n'
    out += ' * License: GPLv2 or later\n'
    out += ' * License URI: https://www.gnu.org/licenses/gpl-2.0.html\n'
    out += ' **/'

    text = None

    with open(bin + '/../details-and-file-upload.php', 'r') as file:
        text = file.read()
        text = re.sub(r'\/\*\*(?:.|\n)+\*\*\/', out, text)

    with open(bin + '/../details-and-file-upload.php', 'w+') as file:
        file.write(text)

generate_md()
generate_txt()
generate_comment()