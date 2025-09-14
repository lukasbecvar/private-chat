# node js builder image
FROM node:22 AS builder

# set working directory
WORKDIR /app

# copy npm configuration
COPY package.json package-lock.json* ./

# install dependencies
RUN npm install

# copy app files
COPY . .

# build production app
RUN npm run build

# build nginx image
FROM nginx:stable-alpine

# remove default nginx index page
RUN rm -rf /usr/share/nginx/html/*

# copy builded app to image
COPY --from=builder /app/dist /usr/share/nginx/html

# expose port 80
EXPOSE 80

# start nginx server
CMD ["nginx", "-g", "daemon off;"]
