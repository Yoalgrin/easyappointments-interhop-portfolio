# How to modify the CSS of your application?

This tutorial will help you modify the CSS (colors and layouts) of your **EasyAppointments** application. To do so, you just have to follow the following steps and let your creativity speak for itself 

# The CSS files

The **CSS** files, which will allow you to modify your application, can be found in the following folder : 

projet_name/assets/css

Inside this folder are several **xxx.css** files:

<img width="407" alt="Capture d’écran 2022-12-08 à 04 28 02" src="https://user-images.githubusercontent.com/101267251/206349587-1d7353d9-cf36-4327-bd30-1d64aa8f446f.png">

1. **backend.css** -> which allows to manage the css of the application backend.
2. **error404.css** -> which allows to manage the css of the 404 error message.
3. **forgot_password.css** -> which allows to manage the css of the page "forgotten password".
4. **frontend.css** -> which allows to manage the css of the frontend of the application.
5. **general.css** -> which allows to manage the css of the common parts of the application (header, footer, navbar).
6. **installation.css** -> which allows to manage the css of the installation page of the project (the one which allows to create the first user)
7. **login.css** -> which allows to manage the css of the login page of the application.
8. **logout.css** -> which allows you to manage the css of the application's logout page.
9. **no_privileges.css** -> which allows to manage the css part of the page "privileges of a user".
10. **update.css** -> which allows you to manage the css part of the application update page

## How to change the colors of the application?

There are several ways to change the colors and layout of the application, but the easiest and quickest way is as follows: 

**Open the application** in your browser and right click on the part you want to change.

<img width="1728" alt="Capture d’écran 2022-12-08 à 03 57 37" src="https://user-images.githubusercontent.com/101267251/206349699-b6e41b90-23b4-4b8f-843c-c57e0a19b047.png">

Select **"inspect "** and you will see a tab open on the right of your browser.

<img width="1728" alt="Capture d’écran 2022-12-08 à 03 57 48" src="https://user-images.githubusercontent.com/101267251/206349774-6440bcc6-df1f-48ca-85d8-6a739f47631c.png">

This allows you, in our case, to **read the code of the page** and to find the id of the object you want to modify. 
The advantage of this method is that it **allows you to test the modifications before applying them in your IDE**. You just have to modify the color directly in your browser and once the final color is chosen, **transcribe it in the CSS file**. It works the same way for the **layout, paddings, sizes, display, fonts, etc, etc**.

<img width="342" alt="Capture d’écran 2022-12-08 à 03 57 59" src="https://user-images.githubusercontent.com/101267251/206350028-c713fe95-e118-4bd5-968f-8695d7ea63c7.png">

Once you have the id of the object you are looking for and you have made your choice on the modifications you want to make, all you have to do is go to your **IDE, launch the application and search, in the CSS folder, for the file corresponding to the page you want to modify**.
In our case, we will open the file **"frontend.css "**.
Inside this file, we will have to find the id of what we are looking for. In this case :
**#book-appointment-wizzard #header** in which you can change the **"background "**.

<img width="410" alt="Capture d’écran 2022-12-08 à 04 33 57" src="https://user-images.githubusercontent.com/101267251/206350436-f7d1f3ce-441d-46de-ad04-2d7c1d2bcee5.png">

In **CSS** there are several methods to **modify a color** :

1. color : crimson; 
2. color : rgb(255,0,0); 
3. color : hsl(16,100%,50%); 
4. color : #FF00FF;

You will find here all **usable colors** :

https://htmlcolorcodes.com/fr/

It's up to you to make **your choice**.

## Last step in the modification process.

Once you have made your **changes**, they may not take effect immediately.

**Two solutions:** 

1. Either, they are **taken into account immediately** and in this case, it is the end of this tutorial.

2. Or, they are **not taken into account** and in this case, you just have to do a **"run build "** in the terminal of your ide so that the IDE compiles the files and takes into account the modifications.
