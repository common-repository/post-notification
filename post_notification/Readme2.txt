
Post Notifcatin; An E-mail notification Plugin for Wordpress
Copyright (C) 2009  Moritz Strübe, and others. See details below.

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.





#-----------------------------------------------------------------------------------------------------------------#
# Info                                                                                                            #
#-----------------------------------------------------------------------------------------------------------------#
# Titel: Post Notification                                                                                        #
# I worked this plugin over which was maintaind by Frank Bueltge.                                                 #
# Changes:                                                                                                        #
#  - No special install scripts have to be run                                                                    #
#  - The \r\n - problem some mailsservers have is configurable                                                    #
#  - WP-Coding Standards are applied as far as possible                                                           #
#  - Mails are sent after beeing published.                                                                       #
#  - You can decide how many mails are sent at once and how long the pause should be before sending the next mails#
#  - Some security-issues were solved                                                                             #
#  - The HTML to text - conversaion was changed                                                                   #
#  - Umlauts work                                                                                                 #
#  - The name was changed                                                                                         #
#  - The Newsletter-function was deaktivated. It'l come back in one of the next versions.                         #
#  - I18N is on it's way. Maby in one of the next versions.                                                       #
# see Changelog.txt for furter changes                                                                            #
#-----------------------------------------------------------------------------------------------------------------#


#----------------------------------------------------------#
# Based ON                                                 #
#----------------------------------------------------------#
# Titel: Newsletter (de) v2.3.1 rev5                       #
#                                                          #
# Das Plugin stammt im Original von Brian Groce,           #
# der es auf Basis von Jon Anhold erstellte und wurd       #
# von Frank Bï¿½ltge ins deutsche ï¿½bersetzt. Auï¿½erdem wurden #
# einige kleine Modifikationen von ihm im Code vorgenommen.#
#                                                          #
# Autor: Frank Bueltge http://bueltge.de                   #
# Datum: Mai 2006                                          #
#                                                          #
# Notitz: Die Version wurde unter WP1.5 und 2.0 getestet.  #
#----------------------------------------------------------#

#------------------------------------------------------------------------------------------------------------------#
# ACKNOWLEDGEMENTS by Frank                                                                                        #
#------------------------------------------------------------------------------------------------------------------#
#                                                                                                                  #
# Thanks to Brian Groce (http://watershedstudio.com/portfolio/software/wp-email-notification.html) for his plugin  #
# "WP Email Notification"                                                                                          #
# Also thanks to Mareike Hybsier (http://www.die-programmiererin.de) for the tests, informations and modifikations.#
#------------------------------------------------------------------------------------------------------------------#



#----------------------------------------------------------#
# BASED ON                                                 #
#----------------------------------------------------------#
# Title: E-Mail Notification                               #
# Author: Brian Groce (http://briangroce.com)              #
# Date: December 8, 2005                                   #
# Version: 2.3.1                                           #
#                                                          #
# Note: This was created for and tested in WordPress v1.5  #
#----------------------------------------------------------#


#----------------------------------------------------------#
# CREDITS                                                  #
#----------------------------------------------------------#
# BASED ON                                                 #
#----------------------------------------------------------#
# Email notification for WordPress.                        #
# Jon Anhold <jon@imagesafari.com> 11/2003                 #
# Please keep this attribution in place.                   #
#                                                          #
#----------------------------------------------------------#
# PLUGIN BY                                                #
#----------------------------------------------------------#
# Brian Groce :: watershedstudio.com :: briangroce.com     #
#----------------------------------------------------------#