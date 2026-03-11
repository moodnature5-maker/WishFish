#!/bin/bash

echo "Adding changes..."
git add .

echo "Enter commit message:"
read msg

echo "Committing..."
git commit -m "$msg"

echo "Pushing to GitHub..."
git push origin main

echo "Done!"