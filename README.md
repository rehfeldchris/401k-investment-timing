This is a quickly written script that helped me investigate optimal 401k investments.  

I was curious to answer the question "Should I evenly invest into my 401k a little each month for all 12 months of the year, or should I invest it all in the first 3 months, or maybe all in the first month of the year?"

It works by using a *backtesting* approach, where I acquired a decade of historical data for the stock ticker SPY(from yahoo finance), and then basically
used that to compute the outcome of the different strategies.

----

The sim lets me specify which months to buy in, using exactly 1 purchase per month. I decided
to compute the average stock price on a per month basis. Stock purchases were
made using this average price, and they were executed on the 15th of the month if a purchase was
made that month.

I also assume enrollment in dividend reinvestment, and purchase more shares on the divident payment date. If the
data is missing the payment date, I pick a date about 2 weeks after the EX date, and purchase the DRIP shares
at that day's price.

You set a yearly contribution, and it splits that equally into purchases among the months you specified.

The sim works by just looping through all days from start to end, at each day it checks if it should buy stock
or collect a dividend, and prints out event messages along the way. It also prints how many shares you have
at the end, and higher is better of course.

I ran the sim using $5500 per year, and here's the results vs which months I bought in:

months, num shares at the end
1,2,3 410.86
4,5,6 392.32
7,8,9 391.06
10,11,12 390.83
1 412.73

As you can see, the sim says it's better to buy at the start of the year. Although, that 
might just be due to collecting a few more dividends the first year and letting that compound 
over the years.