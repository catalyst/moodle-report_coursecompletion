This plugin allows you to report on course completions globally across a site, it currently has been tested for Moodle 3.1 and 3.2 with Mysql(MariaDB) or Postgresql

Course completions must be configured and enabled properly for the courses you want to report on.

The report appears under the reports subsection of the site admin menu.

The following search criteria are available

User

*Firstname
*Lastname
*Email
*Cohort (if any)
*Active user (not suspended or deleted)
*Suspended
*Deleted

Course

*Category
*Name
*Completion State (Complete/Incomplete)

Time completed

*Before time
*After time

Time started

*Before time
*After time

Finally you can change the search conditions to be inclusive or exclusive (AND vs OR)


The table shows the following data with pagination

*User's full name (sort by first/last and linked to profile)
*Email
*Course (linked to course)
*Timestarted
*Timecompleted

Finally there is a button to export the current set of user selected by your choices to csv


This plugin was developed at the Catalyst Open Source Academy with the assistance of five high school students! Thank you to:

*
*
*
*

With assistance from
Francis Devine <francis(at)catalyst(dot)net(dot)nz>
