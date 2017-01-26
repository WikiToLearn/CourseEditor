# CourseEditor
### Introduction
First of all, what's **Course Editor**?
It is a MediaWiki extension which helps users to create structured courses in a interactive and easy way.

The units of a course are chapters and sections, so the structure is the following:
a **course** is composed by one or more **chapters** that are composed by one or more **sections**.

                 Course
                    |
                   / \
                  /   \
           Chapter1   Chapter2
              |          |
             / \      Section1
            /   \
      Section1  Section2

Moreover the course could be **public** or **private**. A private course is created _"behind"_ the user page, the public one in the WikiToLearn department or topic from which the user started the creation process. 
     
### Essential units
Now we're going to delve into specific details about each essential unit. This bulleted list describes the attributes associated with each of the parties already reflected.

- _Course_:
  - Title (name)
  - Topic
  - Description
  - Bibliography
  - Exercises
  - Books
  - External references
  - Imported (yes/no)
  - Reviewed (yes/no)
- _Chapter_:
  - Title (name)
- _Section_:
  - Title (name)
  
As you can see, the course has lots of attributes. They are grouped into page we called metadata page. We will address the implementation details in a moment.
The most important thing for now is to have understood the structure and attributes that can be defined for a course.

### Implementation details
